<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;
use App\Models\SeleccionExamenOcupacional;
use App\Models\SeleccionIngreso;
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
     *     estado?: string|null,
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

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '' && array_key_exists($estado, SeleccionExamenOcupacional::ESTADO_LABELS)) {
            $query->where('estado', $estado);
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
            'estado' => (string) ($validated['estado'] ?? SeleccionExamenOcupacional::ESTADO_SOLICITADO),
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

    /**
     * Ingreso más reciente de una cédula (para prellenar Nuevo examen).
     */
    public function latestIngresoByDocument(string $documentNumber): ?SeleccionIngreso
    {
        $documentNumber = trim($documentNumber);

        if ($documentNumber === '') {
            return null;
        }

        return SeleccionIngreso::query()
            ->where('document_number', $documentNumber)
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Campos coincidentes Ingreso → Examen (nombres de input del formulario Examen).
     * Teléfono del ingreso = Celular del examen. El resto de campos del examen no existen en Ingreso.
     *
     * @return array{document_number: string, full_name: string, email: string, phone: string, city_code: string, position_code: string, commercial_client_id: string, responsable_user_id: string}
     */
    public function prefillFromIngreso(SeleccionIngreso $ingreso): array
    {
        return [
            'document_number' => (string) $ingreso->document_number,
            'full_name' => (string) $ingreso->full_name,
            'email' => (string) $ingreso->email,
            'phone' => (string) $ingreso->phone,
            'city_code' => (string) $ingreso->city_code,
            'position_code' => (string) $ingreso->position_code,
            'commercial_client_id' => $ingreso->commercial_client_id ? (string) $ingreso->commercial_client_id : '',
            'responsable_user_id' => $ingreso->responsable_user_id ? (string) $ingreso->responsable_user_id : '',
        ];
    }

    /**
     * Valores auditables del examen (fechas Y-m-d) para before/after del log.
     *
     * @return array<string, mixed>
     */
    public function auditSnapshot(SeleccionExamenOcupacional $examen): array
    {
        return [
            'document_number' => $examen->document_number,
            'full_name' => $examen->full_name,
            'position_name' => $examen->position_name,
            'servicio_sector' => $examen->servicio_sector,
            'commercial_client_id' => $examen->commercial_client_id,
            'eps_name' => $examen->eps_name,
            'afp_name' => $examen->afp_name,
            'birth_date' => optional($examen->birth_date)?->format('Y-m-d'),
            'city_name' => $examen->city_name,
            'address' => $examen->address,
            'phone' => $examen->phone,
            'marital_status_name' => $examen->marital_status_name,
            'fecha_arl' => optional($examen->fecha_arl)?->format('Y-m-d'),
            'estado' => $examen->estado,
            'email' => $examen->email,
            'solicitud_status_name' => $examen->solicitud_status_name,
            'responsable_user_id' => $examen->responsable_user_id,
        ];
    }
}
