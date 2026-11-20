<?php

namespace App\Services\Matrices;

use App\Models\Matrices\MatrixField;
use App\Models\Matrices\MatrixSubmission;
use App\Models\Matrices\MatrixSubmissionAttachment;
use App\Models\User;
use App\Services\Users\UserSignatureService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Adjuntos y firmas de un registro, en disco privado.
 *
 * Límites: 10 MB por archivo, 10 archivos por registro y 200 MB por matriz.
 */
final class MatrixAttachmentService
{
    public const DISK = 'local';

    public const MAX_FILE_BYTES = 10 * 1024 * 1024;

    public const MAX_FILES_PER_RECORD = 10;

    public const MAX_MATRIX_BYTES = 200 * 1024 * 1024;

    private const MAX_SIGNATURE_BYTES = 2 * 1024 * 1024;

    public function __construct(
        private readonly UserSignatureService $signatureService,
    ) {}

    /**
     * @param  Collection<int, MatrixField>  $fields
     * @param  array<string, mixed>  $payload
     */
    public function storeForSubmission(MatrixSubmission $submission, Collection $fields, array $payload, User $user): void
    {
        foreach ($fields as $field) {
            if ($field->type === MatrixField::TYPE_FILE) {
                $this->storeFiles($submission, $field, $payload['files'][$field->field_key] ?? []);
            }

            if ($field->type === MatrixField::TYPE_SIGNATURE) {
                $this->storeSignature($submission, $field, $payload, $user);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $files
     */
    private function storeFiles(MatrixSubmission $submission, MatrixField $field, array $files): void
    {
        $files = array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));

        if ($files === []) {
            return;
        }

        $existing = MatrixSubmissionAttachment::query()
            ->where('submission_id', $submission->id)
            ->where('field_id', $field->id)
            ->count();

        if ($existing + count($files) > self::MAX_FILES_PER_RECORD) {
            throw ValidationException::withMessages([
                "files.{$field->field_key}" => "El campo {$field->label} admite máximo ".self::MAX_FILES_PER_RECORD.' archivos por registro.',
            ]);
        }

        $incomingBytes = 0;

        foreach ($files as $file) {
            if ($file->getSize() > self::MAX_FILE_BYTES) {
                throw ValidationException::withMessages([
                    "files.{$field->field_key}" => "Cada archivo de {$field->label} debe pesar máximo 10 MB.",
                ]);
            }

            $incomingBytes += (int) $file->getSize();
        }

        $this->ensureMatrixQuota($submission, $incomingBytes);

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $directory = $this->directory($submission);
            $storedPath = $file->storeAs($directory, Str::uuid()->toString().'.'.$extension, self::DISK);

            MatrixSubmissionAttachment::create([
                'submission_id' => $submission->id,
                'field_id' => $field->id,
                'uploaded_by_user_id' => auth()->id(),
                'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                'stored_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'size_bytes' => (int) $file->getSize(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeSignature(MatrixSubmission $submission, MatrixField $field, array $payload, User $user): void
    {
        $mode = (string) ($payload['signature_mode'][$field->field_key] ?? '');
        $canvas = (string) ($payload['signature_canvas'][$field->field_key] ?? '');

        if ($mode === '') {
            return;
        }

        $existing = MatrixSubmissionAttachment::query()
            ->where('submission_id', $submission->id)
            ->where('field_id', $field->id)
            ->get();

        $directory = $this->directory($submission);
        $fileName = 'firma-'.Str::slug($field->label, '-').'.png';
        $contents = null;

        if ($mode === 'profile') {
            $path = $this->signatureService->copyFromUser($user, $directory.'/'.Str::uuid()->toString().'.png');

            if ($path === null) {
                throw ValidationException::withMessages([
                    "signature_canvas.{$field->field_key}" => 'No tienes una firma registrada en tu perfil. Dibújala en el campo.',
                ]);
            }

            $existing->each(fn (MatrixSubmissionAttachment $attachment) => $this->deleteAttachment($attachment));

            MatrixSubmissionAttachment::create([
                'submission_id' => $submission->id,
                'field_id' => $field->id,
                'uploaded_by_user_id' => $user->id,
                'original_name' => $fileName,
                'stored_path' => $path,
                'mime_type' => 'image/png',
                'size_bytes' => (int) Storage::disk(self::DISK)->size($path),
            ]);

            return;
        }

        if ($mode !== 'draw' || $canvas === '') {
            return;
        }

        $contents = $this->decodeCanvas($canvas, $field);

        $this->ensureMatrixQuota($submission, strlen($contents));

        $path = $directory.'/'.Str::uuid()->toString().'.png';
        Storage::disk(self::DISK)->put($path, $contents);

        $existing->each(fn (MatrixSubmissionAttachment $attachment) => $this->deleteAttachment($attachment));

        MatrixSubmissionAttachment::create([
            'submission_id' => $submission->id,
            'field_id' => $field->id,
            'uploaded_by_user_id' => $user->id,
            'original_name' => $fileName,
            'stored_path' => $path,
            'mime_type' => 'image/png',
            'size_bytes' => strlen($contents),
        ]);
    }

    /**
     * Cantidad de adjuntos por campo, para decidir obligatoriedad al editar.
     *
     * @param  Collection<int, MatrixField>  $fields
     * @return array{files: array<string, int>, signatures: array<string, bool>}
     */
    public function existingState(MatrixSubmission $submission, Collection $fields): array
    {
        $counts = [];
        $signatures = [];

        $attachments = MatrixSubmissionAttachment::query()
            ->where('submission_id', $submission->id)
            ->get()
            ->groupBy('field_id');

        foreach ($fields as $field) {
            $fieldAttachments = $attachments->get($field->id) ?? collect();

            if ($field->type === MatrixField::TYPE_FILE) {
                $counts[$field->field_key] = $fieldAttachments->count();
            }

            if ($field->type === MatrixField::TYPE_SIGNATURE) {
                $signatures[$field->field_key] = $fieldAttachments->isNotEmpty();
            }
        }

        return ['files' => $counts, 'signatures' => $signatures];
    }

    public function deleteAttachment(MatrixSubmissionAttachment $attachment): void
    {
        $disk = Storage::disk(self::DISK);

        if ($attachment->stored_path !== '' && $disk->exists($attachment->stored_path)) {
            $disk->delete($attachment->stored_path);
        }

        $attachment->delete();
    }

    public function deleteSubmissionFiles(MatrixSubmission $submission): void
    {
        $disk = Storage::disk(self::DISK);

        MatrixSubmissionAttachment::query()
            ->where('submission_id', $submission->id)
            ->get()
            ->each(function (MatrixSubmissionAttachment $attachment) use ($disk): void {
                if ($attachment->stored_path !== '' && $disk->exists($attachment->stored_path)) {
                    $disk->delete($attachment->stored_path);
                }
            });
    }

    private function ensureMatrixQuota(MatrixSubmission $submission, int $incomingBytes): void
    {
        $used = (int) MatrixSubmissionAttachment::query()
            ->whereHas('submission', fn ($query) => $query->where('matrix_id', $submission->matrix_id))
            ->sum('size_bytes');

        if ($used + $incomingBytes > self::MAX_MATRIX_BYTES) {
            throw ValidationException::withMessages([
                'files' => 'La matriz alcanzó el límite de 200 MB en adjuntos. Elimina archivos antes de subir más.',
            ]);
        }
    }

    private function decodeCanvas(string $canvas, MatrixField $field): string
    {
        $prefix = 'data:image/png;base64,';
        $encoded = substr($canvas, strlen($prefix));
        $contents = base64_decode($encoded, true);

        if (! is_string($contents) || $contents === '') {
            throw ValidationException::withMessages([
                "signature_canvas.{$field->field_key}" => 'La firma dibujada no se pudo procesar.',
            ]);
        }

        if (strlen($contents) > self::MAX_SIGNATURE_BYTES) {
            throw ValidationException::withMessages([
                "signature_canvas.{$field->field_key}" => 'La firma dibujada es demasiado pesada.',
            ]);
        }

        return $contents;
    }

    private function directory(MatrixSubmission $submission): string
    {
        return 'matrices/'.$submission->matrix_id.'/'.$submission->id;
    }

    /**
     * Suma de bytes usados por la matriz (para mostrar el consumo en la vista).
     */
    public function matrixUsage(MatrixSubmission $submission): string
    {
        $used = (int) MatrixSubmissionAttachment::query()
            ->whereHas('submission', fn ($query) => $query->where('matrix_id', $submission->matrix_id))
            ->sum('size_bytes');

        return number_format($used / 1048576, 1, ',', '.').' MB';
    }

    /**
     * Verificación de existencia del archivo (descarga/preview).
     */
    public function fileExists(MatrixSubmissionAttachment $attachment): bool
    {
        return Storage::disk(self::DISK)->exists((string) $attachment->stored_path);
    }
}
