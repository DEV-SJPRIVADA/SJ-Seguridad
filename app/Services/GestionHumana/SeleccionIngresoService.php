<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Models\SeleccionIngreso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SeleccionIngresoService
{
    /**
     * @param  array{
     *     q?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     commercial_client_id?: int|string|null,
     *     responsable_user_id?: int|string|null,
     *     city_code?: string|null,
     *     position_code?: string|null,
     * }  $filters
     * @return Builder<SeleccionIngreso>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = SeleccionIngreso::query()
            ->with(['commercialClient:id,name', 'uniform:id,name', 'responsable:id,name']);

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('document_number', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->whereDate('fecha_ingreso', '>=', $dateFrom);
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->whereDate('fecha_ingreso', '<=', $dateTo);
        }

        $clientId = $filters['commercial_client_id'] ?? null;
        if ($clientId !== null && $clientId !== '') {
            $query->where('commercial_client_id', (int) $clientId);
        }

        $responsableId = $filters['responsable_user_id'] ?? null;
        if ($responsableId !== null && $responsableId !== '') {
            $query->where('responsable_user_id', (int) $responsableId);
        }

        $cityCode = trim((string) ($filters['city_code'] ?? ''));
        if ($cityCode !== '') {
            $query->where('city_code', $cityCode);
        }

        $positionCode = trim((string) ($filters['position_code'] ?? ''));
        if ($positionCode !== '') {
            $query->where('position_code', $positionCode);
        }

        if ($ordered) {
            $query->orderByDesc('fecha_ingreso')->orderByDesc('id');
        }

        return $query;
    }

    /**
     * @return Collection<int, SeleccionIngreso>
     */
    public function findDuplicatesByDocument(string $documentNumber, ?int $excludeId = null): Collection
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return collect();
        }

        $query = SeleccionIngreso::query()
            ->where('document_number', $documentNumber)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id');

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->get(['id', 'document_number', 'full_name', 'fecha_ingreso', 'commercial_client_id']);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function payloadWithSnapshots(array $validated): array
    {
        $city = $this->activeCatalogName('city', (string) $validated['city_code']);
        $position = $this->activeCatalogName('position', (string) $validated['position_code']);
        $blood = $this->activeCatalogName('blood_type', (string) $validated['blood_type_code']);

        return [
            'document_number' => trim((string) $validated['document_number']),
            'full_name' => trim((string) $validated['full_name']),
            'email' => trim((string) $validated['email']),
            'phone' => trim((string) $validated['phone']),
            'city_code' => (string) $validated['city_code'],
            'city_name' => $city,
            'position_code' => (string) $validated['position_code'],
            'position_name' => $position,
            'commercial_client_id' => (int) $validated['commercial_client_id'],
            'shirt_size' => trim((string) $validated['shirt_size']),
            'pants_size' => trim((string) $validated['pants_size']),
            'shoes_size' => trim((string) $validated['shoes_size']),
            'requisition_uniform_id' => (int) $validated['requisition_uniform_id'],
            'fecha_ingreso' => $validated['fecha_ingreso'],
            'blood_type_code' => (string) $validated['blood_type_code'],
            'blood_type_name' => $blood,
            'reemplaza_a' => trim((string) $validated['reemplaza_a']),
            'responsable_user_id' => (int) $validated['responsable_user_id'],
            'jefe_ope' => trim((string) $validated['jefe_ope']),
        ];
    }

    /**
     * @return list<array{id: int, full_name: string, fecha_ingreso: string|null}>
     */
    public function duplicateSummaries(Collection $matches): array
    {
        return $matches
            ->map(fn (SeleccionIngreso $row): array => [
                'id' => $row->id,
                'full_name' => $row->full_name,
                'fecha_ingreso' => optional($row->fecha_ingreso)?->format('Y-m-d'),
            ])
            ->values()
            ->all();
    }

    private function activeCatalogName(string $type, string $code): string
    {
        $item = PayrollCatalogItem::query()
            ->ofType($type)
            ->active()
            ->where('code', $code)
            ->first();

        return $item?->name ?? $code;
    }
}
