<?php

namespace App\Services\Calidad;

use App\Models\Calidad\MtGi11CargoResponsable;
use App\Models\Calidad\MtGi11Catalog;
use App\Models\Calidad\MtGi11Estado;
use App\Models\Calidad\MtGi11Proceso;
use App\Models\Calidad\MtGi11Solicitud;
use App\Models\Calidad\MtGi11TipoDocumento;
use App\Models\Calidad\MtGi11TipoSolicitud;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Administración y lectura de los cinco catálogos de MT-GI-11.
 */
final class MtGi11CatalogService
{
    /**
     * @return array<string, class-string<MtGi11Catalog>>
     */
    public function managedTypes(): array
    {
        return [
            'procesos' => MtGi11Proceso::class,
            'tipos_documento' => MtGi11TipoDocumento::class,
            'tipos_solicitud' => MtGi11TipoSolicitud::class,
            'cargos_responsable' => MtGi11CargoResponsable::class,
            'estados' => MtGi11Estado::class,
        ];
    }

    public function isManagedType(string $type): bool
    {
        return isset($this->managedTypes()[$type]);
    }

    /**
     * @return class-string<MtGi11Catalog>
     */
    public function modelClassFor(string $type): string
    {
        $class = $this->managedTypes()[$type] ?? null;
        abort_unless($class !== null, 404);

        return $class;
    }

    public function findItemOrFail(string $type, int $id): MtGi11Catalog
    {
        /** @var MtGi11Catalog $item */
        $item = $this->modelClassFor($type)::query()->findOrFail($id);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function catalogsForAdmin(): array
    {
        $out = [];

        foreach ($this->managedTypes() as $type => $class) {
            $out[$type] = $class::query()->ordered()->get();
        }

        return $out;
    }

    public function labelFor(string $type): string
    {
        return match ($type) {
            'procesos' => 'Proceso',
            'tipos_documento' => 'Tipo de documento',
            'tipos_solicitud' => 'Tipo de solicitud',
            'cargos_responsable' => 'Cargo del responsable',
            'estados' => 'Estado',
            default => $type,
        };
    }

    /**
     * Opciones listas para `<x-searchable-select>` (solo activos, o incluyendo el valor actual).
     *
     * @return list<array{value: string, label: string}>
     */
    public function optionsFor(string $type, ?int $includeId = null): array
    {
        $class = $this->modelClassFor($type);

        return $class::query()
            ->where(function ($query) use ($includeId): void {
                $query->where('is_active', true);
                if ($includeId !== null) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->ordered()
            ->get(['id', 'name'])
            ->map(fn ($item): array => ['value' => (string) $item->id, 'label' => (string) $item->name])
            ->all();
    }

    /**
     * Código técnico único derivado del nombre.
     */
    public function uniqueCode(string $type, string $name, ?int $ignoreId = null): string
    {
        $class = $this->modelClassFor($type);
        $base = Str::upper(Str::slug(Str::ascii($name), '_'));
        $base = trim($base, '_');
        $base = $base === '' ? 'ITEM' : Str::limit($base, 40, '');
        $code = $base;
        $suffix = 2;

        while ($class::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('code', $code)
            ->exists()) {
            $code = Str::limit($base, 40, '').'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    /**
     * Impide borrar un valor en uso; se desactiva en su lugar.
     */
    public function assertDeletable(string $type, MtGi11Catalog $item): void
    {
        $column = match ($type) {
            'procesos' => 'proceso_id',
            'tipos_documento' => 'tipo_documento_id',
            'tipos_solicitud' => 'tipo_solicitud_id',
            'cargos_responsable' => 'cargo_responsable_id',
            'estados' => 'estado_id',
            default => null,
        };

        abort_unless($column !== null, 404);

        $used = MtGi11Solicitud::withTrashed()->where($column, $item->getKey())->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'catalog' => 'No se puede eliminar: hay solicitudes MT-GI-11 que usan este valor. Puede desactivarlo.',
            ]);
        }
    }
}
