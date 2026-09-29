<?php

namespace App\Services\GestionHumana;

use App\Support\DocumentNumberListParser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class AcreditacionValidacionesRowFilter
{
    public function __construct(
        private readonly DocumentNumberListParser $documentNumberListParser,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function apply(Collection $rows, Request $request, string $cola): Collection
    {
        return $this->applyFilters($rows, $this->filtersFromRequest($request), $cola);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function applyFilters(Collection $rows, array $filters, string $cola): Collection
    {
        $documentNumber = $filters['document_number'] ?? '';
        $documentNumbers = $this->documentNumberListParser->fromInput($filters['document_numbers'] ?? null);
        $fullName = $filters['full_name'] ?? '';
        $cargo = $filters['cargo'] ?? '';
        $cargoApo = $filters['cargo_apo'] ?? '';
        $estado = $filters['estado'] ?? '';
        $personalTipo = $filters['personal_tipo'] ?? '';

        if (
            $documentNumber === ''
            && $documentNumbers === []
            && $fullName === ''
            && $cargo === ''
            && $cargoApo === ''
            && $estado === ''
            && $personalTipo === ''
        ) {
            return $rows;
        }

        return $rows->filter(function (array $row) use (
            $documentNumber,
            $documentNumbers,
            $fullName,
            $cargo,
            $cargoApo,
            $estado,
            $personalTipo,
            $cola,
        ): bool {
            if ($documentNumber !== '' && ! $this->contains((string) ($row['document_number'] ?? ''), $documentNumber)) {
                return false;
            }

            if (
                $documentNumbers !== []
                && ! $this->documentNumberListParser->matches((string) ($row['document_number'] ?? ''), $documentNumbers)
            ) {
                return false;
            }

            if ($fullName !== '' && ! $this->contains((string) ($row['full_name'] ?? ''), $fullName)) {
                return false;
            }

            if ($cargo !== '' && ! $this->contains((string) ($row['cargo'] ?? ''), $cargo)) {
                return false;
            }

            if (
                $cargoApo !== ''
                && $cola !== AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION
                && ! $this->contains((string) ($row['cargo_apo'] ?? ''), $cargoApo)
            ) {
                return false;
            }

            if (
                $estado !== ''
                && $cola !== AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION
                && ! $this->estadoMatches($row, $estado)
            ) {
                return false;
            }

            if (
                $personalTipo !== ''
                && $cola === AcreditacionValidacionesResultStore::COLA_SIN_ACREDITACION
                && mb_strtoupper(trim((string) ($row['personal_tipo'] ?? '')), 'UTF-8') !== $personalTipo
            ) {
                return false;
            }

            return true;
        })->values();
    }

    /**
     * @return array{
     *     document_number: string,
     *     document_numbers: list<string>,
     *     full_name: string,
     *     cargo: string,
     *     cargo_apo: string,
     *     estado: string,
     *     personal_tipo: string,
     * }
     */
    public function filtersFromRequest(Request $request): array
    {
        $estado = trim($request->string('estado')->toString());
        if ($estado === 'todos') {
            $estado = '';
        }

        $personalTipo = mb_strtoupper(trim($request->string('personal_tipo')->toString()), 'UTF-8');
        if (! in_array($personalTipo, ['OPERATIVO', 'ADMINISTRATIVO'], true)) {
            $personalTipo = '';
        }

        return [
            'document_number' => trim($request->string('document_number')->toString()),
            'document_numbers' => $this->documentNumberListParser->fromInput(
                $request->input('document_numbers'),
            ),
            'full_name' => trim($request->string('full_name')->toString()),
            'cargo' => trim($request->string('cargo')->toString()),
            'cargo_apo' => trim($request->string('cargo_apo')->toString()),
            'estado' => $estado,
            'personal_tipo' => $personalTipo,
        ];
    }

    private function contains(string $haystack, string $needle): bool
    {
        return str_contains(
            mb_strtolower($haystack, 'UTF-8'),
            mb_strtolower($needle, 'UTF-8'),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function estadoMatches(array $row, string $estado): bool
    {
        $code = mb_strtoupper(trim((string) ($row['estado'] ?? '')), 'UTF-8');
        $wanted = mb_strtoupper(trim($estado), 'UTF-8');

        return $code === $wanted;
    }
}
