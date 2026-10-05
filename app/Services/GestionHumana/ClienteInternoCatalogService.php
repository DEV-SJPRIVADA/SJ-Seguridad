<?php

namespace App\Services\GestionHumana;

use App\Models\ClienteInternoEstado;
use App\Models\ClienteInternoTipoSolicitud;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ClienteInternoCatalogService
{
    public const TYPE_ESTADOS = 'estados';

    public const TYPE_TIPOS_SOLICITUD = 'tipos-solicitud';

    /**
     * @return list<string>
     */
    public function managedTypes(): array
    {
        return [
            self::TYPE_ESTADOS,
            self::TYPE_TIPOS_SOLICITUD,
        ];
    }

    public function isManagedType(string $type): bool
    {
        return in_array($type, $this->managedTypes(), true);
    }

    /**
     * @return array<string, string>
     */
    public function typeLabels(): array
    {
        return [
            self::TYPE_ESTADOS => 'Estados',
            self::TYPE_TIPOS_SOLICITUD => 'Tipos de solicitud',
        ];
    }

    /**
     * @return class-string<ClienteInternoEstado|ClienteInternoTipoSolicitud>
     */
    public function modelClassFor(string $type): string
    {
        return match ($type) {
            self::TYPE_ESTADOS => ClienteInternoEstado::class,
            self::TYPE_TIPOS_SOLICITUD => ClienteInternoTipoSolicitud::class,
            default => throw new \InvalidArgumentException("Catálogo no gestionado: {$type}"),
        };
    }

    /**
     * @return array{code: string, name: string}
     */
    public function columnLabelsFor(string $type): array
    {
        return match ($type) {
            self::TYPE_ESTADOS => ['code' => 'Código', 'name' => 'Nombre'],
            self::TYPE_TIPOS_SOLICITUD => ['code' => 'Código', 'name' => 'Nombre'],
            default => ['code' => 'Código', 'name' => 'Nombre'],
        };
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     items: Collection<int, ClienteInternoEstado|ClienteInternoTipoSolicitud>,
     *     columnLabels: array{code: string, name: string},
     *     emptyMessage: string|null
     * }>
     */
    public function catalogsForAdmin(): array
    {
        $catalogs = [];

        foreach ($this->typeLabels() as $type => $label) {
            $modelClass = $this->modelClassFor($type);
            /** @var Collection<int, ClienteInternoEstado|ClienteInternoTipoSolicitud> $items */
            $items = $modelClass::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $catalogs[] = [
                'key' => $type,
                'label' => $label,
                'items' => $items,
                'columnLabels' => $this->columnLabelsFor($type),
                'emptyMessage' => $type === self::TYPE_TIPOS_SOLICITUD
                    ? 'Aún no hay tipos de solicitud. Cree el primero con el formulario superior antes de registrar solicitudes o importar.'
                    : null,
            ];
        }

        return $catalogs;
    }

    public function findItemOrFail(string $type, int $itemId): Model
    {
        abort_unless($this->isManagedType($type), 404);

        return $this->modelClassFor($type)::query()->findOrFail($itemId);
    }

    /**
     * Bloquea DELETE si el ítem está referenciado en cliente_interno_solicitudes.
     */
    public function hasBusinessReferences(string $type, Model $item): bool
    {
        return match ($type) {
            self::TYPE_ESTADOS => $item instanceof ClienteInternoEstado
                && $item->solicitudes()->exists(),
            self::TYPE_TIPOS_SOLICITUD => $item instanceof ClienteInternoTipoSolicitud
                && $item->solicitudes()->exists(),
            default => false,
        };
    }

    public function tableNameFor(string $type): string
    {
        return match ($type) {
            self::TYPE_ESTADOS => 'cliente_interno_estados',
            self::TYPE_TIPOS_SOLICITUD => 'cliente_interno_tipos_solicitud',
            default => throw new \InvalidArgumentException("Catálogo no gestionado: {$type}"),
        };
    }
}
