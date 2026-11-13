<?php

namespace App\Services\PurchaseRequests;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SupplyRequest;
use App\Models\User;
use App\Services\Users\UserSignatureService;

/**
 * Congela las cuatro firmas del FO-AD-07 en el momento de autorizar la OC.
 */
class PurchaseOrderSignatureSnapshotService
{
    public const SLOT_ELABORADO = 'elaborado';

    public const SLOT_REVISOR = 'revisor';

    public const SLOT_APROBADO = 'aprobado';

    public const SLOT_GERENTE = 'gerente';

    public function __construct(
        private readonly UserSignatureService $signatures,
    ) {}

    public function capture(PurchaseOrder $order): void
    {
        $order->loadDocumentRelations();
        $order->loadMissing('aprobacionAprobador');

        $directory = trim((string) config('user-signatures.snapshot_directory', 'purchase-order-signatures'), '/')
            .'/'.$order->getKey();

        $slots = [
            self::SLOT_ELABORADO => $order->createdBy,
            self::SLOT_REVISOR => $this->revisorUser($order),
            self::SLOT_APROBADO => $order->aprobacionAprobador,
            self::SLOT_GERENTE => $this->gerenteUser(),
        ];

        $snapshots = [];

        foreach ($slots as $slot => $user) {
            $path = $user !== null
                ? $this->signatures->copyFromUser($user, $directory.'/'.$slot.'.png')
                : null;

            $snapshots[$slot] = [
                'user_id' => $user?->id,
                'path' => $path,
            ];
        }

        $order->forceFill(['signature_snapshots' => $snapshots])->save();
    }

    /**
     * @return array<string, string|null>
     */
    public function dataUris(PurchaseOrder $order): array
    {
        $uris = [
            self::SLOT_ELABORADO => null,
            self::SLOT_REVISOR => null,
            self::SLOT_APROBADO => null,
            self::SLOT_GERENTE => null,
        ];

        if (! $order->estaAutorizada()) {
            return $uris;
        }

        $snapshots = is_array($order->signature_snapshots) ? $order->signature_snapshots : [];

        foreach ($uris as $slot => $_) {
            $path = $snapshots[$slot]['path'] ?? null;
            $uris[$slot] = is_string($path) ? $this->signatures->dataUriFromPath($path) : null;
        }

        return $uris;
    }

    public function gerenteUser(): ?User
    {
        $email = strtolower(trim((string) config('purchase-orders.gerente_email', '')));

        if ($email === '') {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }

    public function gerenteNombre(): string
    {
        return trim((string) ($this->gerenteUser()?->name ?? ''));
    }

    private function revisorUser(PurchaseOrder $order): ?User
    {
        $source = $order->source;

        if ($source instanceof PurchaseRequest) {
            $source->loadMissing('aprobador');

            return $source->aprobador;
        }

        if ($source instanceof SupplyRequest) {
            $source->loadMissing('qualityReviewer');

            return $source->qualityReviewer;
        }

        return null;
    }
}
