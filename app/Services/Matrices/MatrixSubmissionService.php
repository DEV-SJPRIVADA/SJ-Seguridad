<?php

namespace App\Services\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixField;
use App\Models\Matrices\MatrixSubmission;
use App\Models\Matrices\MatrixSubmissionValue;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registro y edición de filas de una matriz.
 *
 * El folio se calcula con un contador por matriz bloqueado en transacción.
 */
final class MatrixSubmissionService
{
    public function __construct(
        private readonly MatrixSubmissionValidator $validator,
        private readonly MatrixAttachmentService $attachments,
        private readonly MatrixListingService $listing,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(User $user, Matrix $matrix, array $payload): MatrixSubmission
    {
        return DB::transaction(function () use ($user, $matrix, $payload): MatrixSubmission {
            /** @var Matrix $locked */
            $locked = Matrix::query()->lockForUpdate()->findOrFail($matrix->id);

            if ($locked->isArchived()) {
                throw ValidationException::withMessages(['matrix' => 'La matriz está archivada.']);
            }

            $version = $locked->currentVersion;

            if ($version === null || ! $version->isPublished()) {
                throw ValidationException::withMessages(['matrix' => 'La matriz todavía no está publicada.']);
            }

            /** @var Collection<int, MatrixField> $fields */
            $fields = $version->fields()->get();

            $this->validator->validate($fields, $payload);

            $assignedTo = $this->resolveAssignee($matrix, $payload['assigned_to_user_id'] ?? null);

            $locked->increment('submission_counter');

            $submission = MatrixSubmission::create([
                'matrix_id' => $locked->id,
                'matrix_version_id' => $version->id,
                'folio' => sprintf('%s-%04d', $locked->code, (int) $locked->submission_counter),
                'status' => MatrixSubmission::STATUS_SUBMITTED,
                'created_by_user_id' => $user->id,
                'assigned_to_user_id' => $assignedTo,
                'submitted_at' => now(),
            ]);

            $this->persistValues($submission, $fields, $payload);
            $this->attachments->storeForSubmission($submission, $fields, $payload, $user);

            return $submission->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(MatrixSubmission $submission, array $payload, User $user): MatrixSubmission
    {
        return DB::transaction(function () use ($submission, $payload, $user): MatrixSubmission {
            if ($submission->isArchived()) {
                throw ValidationException::withMessages(['submission' => 'El registro está archivado.']);
            }

            $version = $submission->version;

            if ($version === null) {
                throw ValidationException::withMessages(['submission' => 'El registro no tiene una versión válida.']);
            }

            /** @var Collection<int, MatrixField> $fields */
            $fields = $version->fields()->get();
            $state = $this->attachments->existingState($submission, $fields);

            $this->validator->validate($fields, $payload, $state['files'], $state['signatures']);

            $submission->forceFill([
                'assigned_to_user_id' => $this->resolveAssignee($submission->matrix, $payload['assigned_to_user_id'] ?? null),
                'submitted_at' => $submission->submitted_at ?? now(),
            ])->save();

            $this->persistValues($submission, $fields, $payload);
            $this->attachments->storeForSubmission($submission, $fields, $payload, $user);

            return $submission->refresh();
        });
    }

    public function archive(MatrixSubmission $submission): void
    {
        $submission->forceFill([
            'status' => MatrixSubmission::STATUS_ARCHIVED,
            'archived_at' => now(),
        ])->save();
    }

    /**
     * @param  Collection<int, MatrixField>  $fields
     * @param  array<string, mixed>  $payload
     */
    private function persistValues(MatrixSubmission $submission, Collection $fields, array $payload): void
    {
        foreach ($fields as $field) {
            if ($field->isFileType()) {
                continue;
            }

            $raw = $payload['values'][$field->field_key] ?? null;

            $attributes = [
                'value_text' => null,
                'value_number' => null,
                'value_date' => null,
                'value_bool' => null,
                'value_json' => null,
            ];

            $attributes = match ($field->type) {
                MatrixField::TYPE_TEXT,
                MatrixField::TYPE_TEXTAREA => [...$attributes, 'value_text' => $this->textValue($field, $raw)],
                MatrixField::TYPE_SELECT,
                MatrixField::TYPE_RADIO => [...$attributes, 'value_text' => is_scalar($raw) ? (string) $raw : null],
                MatrixField::TYPE_NUMBER,
                MatrixField::TYPE_CURRENCY => [...$attributes, 'value_number' => is_numeric($raw) ? (float) $raw : null],
                MatrixField::TYPE_DATE => [...$attributes, 'value_date' => is_string($raw) && $raw !== '' ? $raw : null],
                MatrixField::TYPE_CHECKBOX => [
                    ...$attributes,
                    'value_json' => is_array($raw) ? array_values(array_map('strval', $raw)) : null,
                ],
                default => $attributes,
            };

            MatrixSubmissionValue::query()->updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'field_id' => $field->id,
                ],
                $attributes,
            );
        }
    }

    private function textValue(MatrixField $field, mixed $raw): ?string
    {
        if (! is_scalar($raw)) {
            return null;
        }

        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        if ((bool) ($field->ruleSet()['preserve_case'] ?? false)) {
            return $value;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    private function resolveAssignee(Matrix $matrix, mixed $assignedTo): ?int
    {
        if ($assignedTo === null || $assignedTo === '') {
            return null;
        }

        $assignedTo = (int) $assignedTo;

        $allowed = $this->listing->assignableUsers($matrix)->pluck('id')->map(fn ($id): int => (int) $id);

        if (! $allowed->contains($assignedTo)) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => 'El usuario asignado no tiene acceso a esta matriz.',
            ]);
        }

        return $assignedTo;
    }
}
