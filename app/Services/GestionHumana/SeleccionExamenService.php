<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Models\SeleccionExamenOcupacional;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SeleccionExamenService
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
     *     solicitud_status_code?: string|null,
     * }  $filters
     * @return Builder<SeleccionExamenOcupacional>
     */
    public function filteredQuery(array $filters, bool $ordered = true): Builder
    {
        $query = SeleccionExamenOcupacional::query()
            ->with(['commercialClient:id,name', 'responsable:id,name']);

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('document_number', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('servicio_sector', 'like', $like);
            });
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->whereDate('fecha_arl', '>=', $dateFrom);
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->whereDate('fecha_arl', '<=', $dateTo);
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

        $solicitudCode = trim((string) ($filters['solicitud_status_code'] ?? ''));
        if ($solicitudCode !== '') {
            $query->where('solicitud_status_code', $solicitudCode);
        }

        if ($ordered) {
            $query->orderByDesc('fecha_arl')->orderByDesc('id');
        }

        return $query;
    }

    /**
     * @return Collection<int, SeleccionExamenOcupacional>
     */
    public function findDuplicatesByDocument(string $documentNumber, ?int $excludeId = null): Collection
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return collect();
        }

        $query = SeleccionExamenOcupacional::query()
            ->where('document_number', $documentNumber)
            ->orderByDesc('fecha_arl')
            ->orderByDesc('id');

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->get(['id', 'document_number', 'full_name', 'fecha_arl', 'commercial_client_id']);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function payloadWithSnapshots(array $validated): array
    {
        $position = $this->activeCatalogName('position', (string) $validated['position_code']);
        $eps = $this->activeCatalogName('eps', (string) $validated['eps_code']);
        $afp = $this->activeCatalogName('afp', (string) $validated['afp_code']);
        $city = $this->activeCatalogName('city', (string) $validated['city_code']);
        $marital = $this->activeCatalogName('marital_status', (string) $validated['marital_status_code']);
        $solicitud = $this->activeCatalogName('seleccion_solicitud_status', (string) $validated['solicitud_status_code']);

        return [
            'document_number' => trim((string) $validated['document_number']),
            'full_name' => trim((string) $validated['full_name']),
            'position_code' => (string) $validated['position_code'],
            'position_name' => $position,
            'servicio_sector' => trim((string) $validated['servicio_sector']),
            'commercial_client_id' => (int) $validated['commercial_client_id'],
            'eps_code' => (string) $validated['eps_code'],
            'eps_name' => $eps,
            'afp_code' => (string) $validated['afp_code'],
            'afp_name' => $afp,
            'birth_date' => $validated['birth_date'],
            'city_code' => (string) $validated['city_code'],
            'city_name' => $city,
            'address' => trim((string) $validated['address']),
            'email' => trim((string) $validated['email']),
            'phone' => trim((string) $validated['phone']),
            'marital_status_code' => (string) $validated['marital_status_code'],
            'marital_status_name' => $marital,
            'fecha_arl' => $validated['fecha_arl'],
            'solicitud_status_code' => (string) $validated['solicitud_status_code'],
            'solicitud_status_name' => $solicitud,
            'responsable_user_id' => (int) $validated['responsable_user_id'],
        ];
    }

    /**
     * @return list<array{id: int, full_name: string, fecha_arl: string|null}>
     */
    public function duplicateSummaries(Collection $matches): array
    {
        return $matches
            ->map(fn (SeleccionExamenOcupacional $row): array => [
                'id' => $row->id,
                'full_name' => $row->full_name,
                'fecha_arl' => optional($row->fecha_arl)?->format('Y-m-d'),
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
