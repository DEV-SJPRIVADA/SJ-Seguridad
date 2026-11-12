<?php

namespace App\Services\PurchaseRequests;

use App\Mail\PurchaseQuotationApprovalRequestedMail;
use App\Mail\PurchaseQuotationApprovalResolvedMail;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestComment;
use App\Models\User;
use App\Services\Access\PurchaseAccessService;
use App\Support\ApplicationUrls;
use App\Support\PurchaseQuotationBatch;
use App\Support\UppercaseFormText;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cotizaciones de la bandeja de Compras, agrupadas por lote.
 *
 * Cada lote representa una OC futura y admite hasta 3 cotizaciones. Compras carga los
 * archivos (PDF/JPG/PNG), elige un director y envía el lote completo. El director
 * aprueba eligiendo una cotización ganadora por lote o rechaza el lote completo.
 * Mientras un lote esté pendiente no se emite la OC. El vínculo con la OC es opcional,
 * pero solo puede usarse la cotización aprobada y seleccionada de cada lote, una vez.
 */
class PurchaseQuotationService
{
    public function __construct(
        private readonly PurchaseRequestAuditLogService $audit,
        private readonly PurchaseAccessService $access,
        private readonly PurchaseQuotationBatchService $batches,
    ) {}

    /**
     * Carga 1..3 archivos como un lote nuevo, o agrega archivos a un lote abierto.
     *
     * @param  array<array-key, UploadedFile|null>  $files
     * @param  array<array-key, string|null>  $descriptions
     */
    public function storeBatch(
        PurchaseRequest $purchaseRequest,
        User $actor,
        ?string $lote,
        ?string $label,
        array $files,
        array $descriptions,
    ): string {
        $this->assertRequestProcessable($purchaseRequest);

        $maxPerBatch = max(1, (int) config('purchase-requests.quotations.max_per_batch', 3));
        $files = array_filter($files, fn ($file): bool => $file instanceof UploadedFile);

        if ($files === []) {
            throw new InvalidArgumentException('Seleccione al menos un archivo de cotización.');
        }

        $lote = trim((string) $lote);
        $existing = collect();

        if ($lote === '') {
            $lote = Str::uuid()->toString();
            $label = $this->nullableText($label);

            if ($label === null) {
                throw new InvalidArgumentException('Indique una referencia para el grupo de cotizaciones.');
            }
        } else {
            $existing = $purchaseRequest->quotations()->where('lote', $lote)->orderBy('lote_orden')->get();

            if ($existing->isEmpty()) {
                throw new InvalidArgumentException('El grupo de cotizaciones ya no existe.');
            }

            $label = (string) $existing->first()->lote_label;
            $batch = $this->batches->group($existing)->first();

            if (! $batch instanceof PurchaseQuotationBatch || ! $batch->puedeAgregarCotizacion($maxPerBatch)) {
                throw new InvalidArgumentException('El grupo ya no admite más cotizaciones.');
            }
        }

        if ($existing->count() + count($files) > $maxPerBatch) {
            throw new InvalidArgumentException('Cada grupo admite hasta '.$maxPerBatch.' cotizaciones.');
        }

        $disk = (string) config('purchase-requests.quotations.disk', 'local');
        $directory = trim((string) config('purchase-requests.quotations.directory', 'purchase-requests'), '/');
        $storedPaths = [];
        $orden = $existing->isEmpty() ? 1 : ((int) $existing->max('lote_orden')) + 1;

        try {
            return DB::transaction(function () use ($purchaseRequest, $actor, $lote, $label, $files, $descriptions, $disk, $directory, &$storedPaths, &$orden): string {
                foreach ($files as $index => $file) {
                    $extension = strtolower((string) $file->getClientOriginalExtension());
                    $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
                    $storedPath = $file->storeAs(
                        $directory.'/'.$purchaseRequest->getKey().'/quotations',
                        Str::uuid()->toString().'.'.$extension,
                        $disk,
                    );
                    $storedPaths[] = $storedPath;

                    $quotation = $purchaseRequest->quotations()->create([
                        'lote' => $lote,
                        'lote_label' => $label,
                        'lote_orden' => $orden++,
                        'uploaded_by' => $actor->getKey(),
                        'original_name' => $this->sanitizeOriginalName($file->getClientOriginalName()),
                        'stored_path' => $storedPath,
                        'mime_type' => $file->getMimeType() ?: null,
                        'size_bytes' => (int) $file->getSize(),
                        'description' => $this->nullableText((string) ($descriptions[$index] ?? '')),
                        'estado' => PurchaseQuotation::ESTADO_CARGADA,
                    ]);

                    $this->audit->logModelChange(
                        eventType: 'purchase_quotation',
                        action: 'uploaded',
                        model: $quotation,
                        before: null,
                        after: ['estado' => $quotation->estado, 'original_name' => $quotation->original_name],
                        metadata: [
                            'purchase_request_id' => $purchaseRequest->getKey(),
                            'folio' => $purchaseRequest->folio(),
                            'lote' => $lote,
                            'lote_label' => $label,
                        ],
                        userId: $actor->getKey(),
                    );
                }

                return $lote;
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk($disk)->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function delete(PurchaseQuotation $quotation, User $actor): void
    {
        $this->assertRequestProcessable($quotation->purchaseRequest);

        if (! $quotation->puedeEliminar()) {
            throw new InvalidArgumentException('Solo se elimina una cotización cargada o rechazada que no esté vinculada a una orden.');
        }

        $quotation->loadMissing('purchaseRequest');

        $this->audit->logModelChange(
            eventType: 'purchase_quotation',
            action: 'deleted',
            model: $quotation,
            before: ['estado' => $quotation->estado, 'original_name' => $quotation->original_name],
            after: null,
            metadata: [
                'purchase_request_id' => $quotation->purchase_request_id,
                'folio' => $quotation->purchaseRequest?->folio(),
                'lote' => $quotation->lote,
                'lote_label' => $quotation->lote_label,
            ],
            userId: $actor->getKey(),
        );

        $quotation->delete();
    }

    /**
     * Envía el lote completo al aprobador elegido. Pueden coexistir varios lotes pendientes.
     */
    public function request(PurchaseRequest $purchaseRequest, string $lote, User $actor, int $approverId): void
    {
        $this->assertRequestProcessable($purchaseRequest);

        if (! $this->access->isValidApprover($approverId)) {
            throw new InvalidArgumentException('Seleccione un director o administrador activo con permiso para autorizar.');
        }

        $quotations = DB::transaction(function () use ($purchaseRequest, $lote, $actor, $approverId): Collection {
            $quotations = $purchaseRequest->quotations()->delLote($lote)->lockForUpdate()->get();

            if ($quotations->isEmpty()) {
                throw new InvalidArgumentException('El grupo de cotizaciones ya no existe.');
            }

            if ($quotations->contains(fn (PurchaseQuotation $quotation): bool => $quotation->estaPendiente())) {
                throw new InvalidArgumentException('Este grupo ya está pendiente de aprobación.');
            }

            if ($quotations->contains(fn (PurchaseQuotation $quotation): bool => $quotation->estaAprobada() || $quotation->estaNoSeleccionada())) {
                throw new InvalidArgumentException('Este grupo ya fue aprobado por el director.');
            }

            if ($quotations->contains(fn (PurchaseQuotation $quotation): bool => $quotation->estaVinculada())) {
                throw new InvalidArgumentException('El grupo tiene una cotización vinculada a una orden de compra.');
            }

            if (! $quotations->every(fn (PurchaseQuotation $quotation): bool => $quotation->puedeParticiparEnLote())) {
                throw new InvalidArgumentException('El grupo tiene cotizaciones que no se pueden enviar a aprobación.');
            }

            foreach ($quotations as $quotation) {
                $quotation->update([
                    'estado' => PurchaseQuotation::ESTADO_PENDIENTE,
                    'aprobador_id' => $approverId,
                    'solicitada_por' => $actor->getKey(),
                    'solicitada_at' => now(),
                    'comentario' => null,
                    'resuelta_at' => null,
                    'seleccionada' => false,
                ]);

                $this->audit->logModelChange(
                    eventType: 'purchase_quotation',
                    action: 'approval_requested',
                    model: $quotation,
                    before: ['estado' => PurchaseQuotation::ESTADO_CARGADA],
                    after: ['estado' => $quotation->estado, 'aprobador_id' => $approverId],
                    metadata: [
                        'purchase_request_id' => $purchaseRequest->getKey(),
                        'folio' => $purchaseRequest->folio(),
                        'lote' => $lote,
                        'lote_label' => $quotation->lote_label,
                    ],
                    userId: $actor->getKey(),
                );
            }

            return $quotations;
        });

        $label = (string) $quotations->first()?->lote_label;

        $this->comment(
            $purchaseRequest,
            $actor,
            'GRUPO DE COTIZACIONES ENVIADO A APROBACIÓN: '.$label
                .' — '.$quotations->count().' COTIZACIÓN(ES) — APROBADOR: '.UppercaseFormText::uppercaseString((string) $quotations->first()?->aprobador?->name)
                .'.',
        );

        $this->notifyApprover($purchaseRequest, $lote, $quotations);
    }

    public function withdraw(PurchaseRequest $purchaseRequest, string $lote, User $actor): void
    {
        $quotations = DB::transaction(function () use ($purchaseRequest, $lote): Collection {
            $quotations = $purchaseRequest->quotations()->delLote($lote)->lockForUpdate()->get();

            if ($quotations->isEmpty()) {
                throw new InvalidArgumentException('El grupo de cotizaciones ya no existe.');
            }

            if (! $quotations->every(fn (PurchaseQuotation $quotation): bool => $quotation->estaPendiente())) {
                throw new InvalidArgumentException('El grupo no tiene una solicitud de aprobación pendiente.');
            }

            foreach ($quotations as $quotation) {
                $quotation->update([
                    'estado' => PurchaseQuotation::ESTADO_CARGADA,
                    'aprobador_id' => null,
                    'solicitada_por' => null,
                    'solicitada_at' => null,
                    'comentario' => null,
                    'resuelta_at' => null,
                    'seleccionada' => false,
                ]);

                $this->audit->logModelChange(
                    eventType: 'purchase_quotation',
                    action: 'approval_withdrawn',
                    model: $quotation,
                    before: ['estado' => PurchaseQuotation::ESTADO_PENDIENTE],
                    after: ['estado' => $quotation->estado],
                    metadata: [
                        'purchase_request_id' => $purchaseRequest->getKey(),
                        'lote' => $lote,
                        'lote_label' => $quotation->lote_label,
                    ],
                    userId: $actor->getKey(),
                );
            }

            return $quotations;
        });

        $this->comment(
            $purchaseRequest,
            $actor,
            'SOLICITUD DE APROBACIÓN RETIRADA: '.(string) $quotations->first()?->lote_label.'.',
        );
    }

    /**
     * El aprobador elige una cotización ganadora del lote o rechaza el lote completo.
     * `channel`: web | email.
     */
    public function resolve(
        PurchaseRequest $purchaseRequest,
        string $lote,
        int $approverId,
        bool $approve,
        ?int $selectedQuotationId,
        ?string $comentario,
        string $channel = 'web',
    ): Collection {
        $comentario = $this->normalize((string) $comentario);

        if (! $approve && $comentario === '') {
            throw new InvalidArgumentException('Indique el motivo del rechazo.');
        }

        $approver = User::query()->findOrFail($approverId);

        $quotations = DB::transaction(function () use ($purchaseRequest, $lote, $approver, $approverId, $approve, $selectedQuotationId, $comentario, $channel): Collection {
            $quotations = $purchaseRequest->quotations()->delLote($lote)->lockForUpdate()->get();

            if ($quotations->isEmpty()) {
                throw new InvalidArgumentException('El grupo de cotizaciones ya no existe.');
            }

            if (! $quotations->every(fn (PurchaseQuotation $quotation): bool => $quotation->estaPendiente())) {
                throw new InvalidArgumentException('Esta solicitud de aprobación ya fue gestionada.');
            }

            if (! $quotations->every(fn (PurchaseQuotation $quotation): bool => (int) $quotation->aprobador_id === $approverId)) {
                throw new InvalidArgumentException('Usted no está asignado para aprobar este grupo de cotizaciones.');
            }

            $selected = null;

            if ($approve) {
                $selected = $quotations->first(fn (PurchaseQuotation $quotation): bool => (int) $quotation->getKey() === (int) $selectedQuotationId);

                if ($selected === null) {
                    throw new InvalidArgumentException('Seleccione la cotización ganadora de este grupo.');
                }
            }

            foreach ($quotations as $quotation) {
                $isSelected = $selected !== null && (int) $quotation->getKey() === (int) $selected->getKey();

                $quotation->update([
                    'estado' => match (true) {
                        $approve && $isSelected => PurchaseQuotation::ESTADO_APROBADA,
                        $approve => PurchaseQuotation::ESTADO_NO_SELECCIONADA,
                        default => PurchaseQuotation::ESTADO_RECHAZADA,
                    },
                    'seleccionada' => $isSelected,
                    'comentario' => $comentario !== '' ? $comentario : null,
                    'resuelta_at' => now(),
                ]);

                $this->audit->logModelChange(
                    eventType: 'purchase_quotation',
                    action: $approve ? 'approval_approved' : 'approval_rejected',
                    model: $quotation,
                    before: ['estado' => PurchaseQuotation::ESTADO_PENDIENTE],
                    after: ['estado' => $quotation->estado, 'seleccionada' => $isSelected],
                    reason: $comentario !== '' ? $comentario : null,
                    metadata: [
                        'purchase_request_id' => $purchaseRequest->getKey(),
                        'lote' => $lote,
                        'lote_label' => $quotation->lote_label,
                        'channel' => $channel,
                    ],
                    userId: $approver->getKey(),
                );
            }

            return $quotations;
        });

        $label = (string) $quotations->first()?->lote_label;
        $selected = $quotations->first(fn (PurchaseQuotation $quotation): bool => $quotation->seleccionada);

        $this->comment(
            $purchaseRequest,
            $approver,
            ($approve ? 'GRUPO DE COTIZACIONES APROBADO: ' : 'GRUPO DE COTIZACIONES RECHAZADO: ').$label
                .($approve && $selected !== null ? ' — COTIZACIÓN SELECCIONADA: '.$selected->descripcionVisible() : '')
                .' — '.UppercaseFormText::uppercaseString((string) $approver->name)
                .($comentario !== '' ? ': '.$comentario : '.'),
        );

        $this->notifyRequester($purchaseRequest, $label, $selected, $approve, $comentario !== '' ? $comentario : null);

        return $quotations;
    }

    public function emailShowUrl(PurchaseRequest $purchaseRequest, string $lote, int $approverId): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.quotation-email.show',
            $this->linkExpiresAt(),
            ['lote' => $lote, 'director' => $approverId],
        );
    }

    public function emailUpdateUrl(PurchaseRequest $purchaseRequest, string $lote, int $approverId): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.quotation-email.update',
            $this->linkExpiresAt(),
            ['lote' => $lote, 'director' => $approverId],
        );
    }

    public function emailFileUrl(PurchaseQuotation $quotation): string
    {
        return ApplicationUrls::temporarySignedRoute(
            'purchase-requests.quotation-email.file',
            $this->linkExpiresAt(),
            ['purchase_quotation' => $quotation->getKey(), 'director' => $quotation->aprobador_id],
        );
    }

    private function linkExpiresAt(): \DateTimeInterface
    {
        return now()->addDays(max(1, (int) config('purchase-requests.quotations.link_days', 7)));
    }

    /**
     * No se cargan ni solicitan cotizaciones en un pedido ya cerrado o rechazado.
     */
    private function assertRequestProcessable(?PurchaseRequest $purchaseRequest): void
    {
        if ($purchaseRequest === null
            || $purchaseRequest->estado !== PurchaseRequest::ESTADO_APROBADO
            || ! in_array($purchaseRequest->estado_compras, [PurchaseRequest::COMPRAS_PENDIENTE, PurchaseRequest::COMPRAS_EN_CURSO], true)) {
            throw new InvalidArgumentException('La solicitud ya no admite cambios en sus cotizaciones.');
        }
    }

    /**
     * @param  Collection<int, PurchaseQuotation>  $quotations
     */
    private function notifyApprover(PurchaseRequest $purchaseRequest, string $lote, Collection $quotations): void
    {
        $quotations->loadMissing(['aprobador', 'solicitadaPor', 'uploadedBy']);
        $approver = $quotations->first()?->aprobador;
        $email = $approver?->email;

        if (filled($email)) {
            $this->send(
                $quotations->first(),
                $email,
                new PurchaseQuotationApprovalRequestedMail(
                    $purchaseRequest,
                    (string) $quotations->first()?->lote_label,
                    $quotations,
                    $this->emailShowUrl($purchaseRequest, $lote, (int) $approver->getKey()),
                ),
            );
        }
    }

    private function notifyRequester(
        PurchaseRequest $purchaseRequest,
        string $label,
        ?PurchaseQuotation $selected,
        bool $approved,
        ?string $comment,
    ): void {
        $requester = $purchaseRequest->quotations()
            ->whereNotNull('solicitada_por')
            ->latest('solicitada_at')
            ->first()
            ?->solicitadaPor;

        $email = $requester?->email;

        if (filled($email)) {
            $this->send(
                $selected,
                $email,
                new PurchaseQuotationApprovalResolvedMail($purchaseRequest, $label, $selected, $approved, $comment),
            );
        }
    }

    private function send(?PurchaseQuotation $quotation, string $email, object $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar el correo de cotización', [
                'purchase_quotation_id' => $quotation?->getKey(),
                'recipient' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function comment(PurchaseRequest $purchaseRequest, User $author, string $body): void
    {
        PurchaseRequestComment::query()->create([
            'purchase_request_id' => $purchaseRequest->getKey(),
            'user_id' => $author->getKey(),
            'body' => $body,
        ]);
    }

    private function normalize(string $text): string
    {
        return trim(UppercaseFormText::uppercaseString(trim($text)));
    }

    private function nullableText(?string $text): ?string
    {
        $text = $this->normalize((string) $text);

        return $text !== '' ? $text : null;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $clean = trim(str_replace(['\\', '/'], '', $name));

        if ($clean === '') {
            return 'cotizacion';
        }

        return Str::limit($clean, 255, '');
    }
}
