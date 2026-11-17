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

    /**
     * Congela las casillas que aún no tienen snapshot (paso 2 / autorización final).
     * Conserva el PNG del revisor si ya se tomó en el paso 1.
     */
    public function capture(PurchaseOrder $order): void
    {
        $order->loadDocumentRelations();
        $order->loadMissing(['aprobacionAprobador', 'aprobacionAreaAprobador']);

        $slots = [
            self::SLOT_ELABORADO => $order->createdBy,
            self::SLOT_REVISOR => $this->revisorUser($order),
            self::SLOT_APROBADO => $order->aprobacionAprobador,
            self::SLOT_GERENTE => $this->gerenteUser(),
        ];

        foreach ($slots as $slot => $user) {
            $this->captureSlot($order, $slot, $user, overwrite: false);
        }
    }

    /**
     * Congela una casilla. En el paso 1 se usa para el director de área / Calidad.
     */
    public function captureSlot(PurchaseOrder $order, string $slot, ?User $user, bool $overwrite = true): void
    {
        $snapshots = is_array($order->signature_snapshots) ? $order->signature_snapshots : [];

        if (! $overwrite && filled($snapshots[$slot]['path'] ?? null)) {
            return;
        }

        $directory = trim((string) config('user-signatures.snapshot_directory', 'purchase-order-signatures'), '/')
            .'/'.$order->getKey();

        $path = $user !== null
            ? $this->signatures->copyFromUser($user, $directory.'/'.$slot.'.png')
            : null;

        $snapshots[$slot] = [
            'user_id' => $user?->id,
            'path' => $path,
        ];

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
        return $this->signatures->gerenteUser();
    }

    public function gerenteNombre(): string
    {
        return $this->signatures->gerenteNombre();
    }

    private function revisorUser(PurchaseOrder $order): ?User
    {
        $order->loadMissing('aprobacionAreaAprobador');

        if ($order->aprobacionAreaAprobador !== null) {
            return $order->aprobacionAreaAprobador;
        }

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
