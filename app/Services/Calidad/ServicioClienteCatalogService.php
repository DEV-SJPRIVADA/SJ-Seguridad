<?php

namespace App\Services\Calidad;

use App\Models\ServicioClienteCaso;
use App\Models\ServicioClienteCategoria;
use App\Models\ServicioClienteFuente;
use App\Models\ServicioClienteResponsable;
use App\Models\ServicioClienteTipologia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServicioClienteCatalogService
{
    /**
     * @return array<string, class-string<Model>>
     */
    public function managedTypes(): array
    {
        return [
            'tipologias' => ServicioClienteTipologia::class,
            'fuentes' => ServicioClienteFuente::class,
            'responsables' => ServicioClienteResponsable::class,
            'categorias' => ServicioClienteCategoria::class,
        ];
    }

    public function isManagedType(string $type): bool
    {
        return isset($this->managedTypes()[$type]);
    }

    /**
     * @return class-string<Model>
     */
    public function modelClassFor(string $type): string
    {
        $class = $this->managedTypes()[$type] ?? null;
        abort_unless($class !== null, 404);

        return $class;
    }

    public function findItemOrFail(string $type, int $id): Model
    {
        return $this->modelClassFor($type)::query()->findOrFail($id);
    }

    /**
     * @return array<string, Collection<int, Model>>
     */
    public function catalogsForAdmin(): array
    {
        $out = [];

        foreach ($this->managedTypes() as $type => $class) {
            $out[$type] = $class::query()->orderBy('sort_order')->orderBy('name')->get();
        }

        return $out;
    }

    public function labelFor(string $type): string
    {
        return match ($type) {
            'tipologias' => 'Tipología',
            'fuentes' => 'Fuente',
            'responsables' => 'Responsable',
            'categorias' => 'Categoría',
            default => $type,
        };
    }

    public function uniqueCode(string $type, string $name, ?int $ignoreId = null): string
    {
        $base = Str::upper(Str::slug(Str::ascii($name), '_'));
        $base = trim($base, '_');
        $base = $base === '' ? 'ITEM' : Str::limit($base, 40, '');
        $class = $this->modelClassFor($type);
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

    public function assertDeletable(string $type, Model $item): void
    {
        $column = match ($type) {
            'tipologias' => 'tipologia_id',
            'fuentes' => 'fuente_id',
            'responsables' => 'responsable_id',
            'categorias' => 'categoria_id',
            default => null,
        };

        abort_unless($column !== null, 404);

        $used = ServicioClienteCaso::query()->where($column, $item->getKey())->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'catalog' => 'No se puede eliminar: hay casos FO-GI-22 que usan este valor.',
            ]);
        }
    }
}
