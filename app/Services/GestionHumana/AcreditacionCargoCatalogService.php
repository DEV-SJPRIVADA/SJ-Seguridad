<?php

namespace App\Services\GestionHumana;

use App\Models\AcreditacionCargo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcreditacionCargoCatalogService
{
    private const ACREDITADOS_TABLE = 'acreditacion_acreditados';

    /**
     * DELETE: bloquear si es la última fila activa con ese cargo_apo
     * y existen acreditados con ese tipo. Sin tabla (legado T2): bloquear por seguridad.
     */
    public function canDelete(AcreditacionCargo $cargo): bool
    {
        if (! $this->isLastActiveForApo($cargo)) {
            return true;
        }

        if (! $this->acreditadosTableExists()) {
            return false;
        }

        return ! $this->hasAcreditadosWithApo($cargo->cargo_apo);
    }

    public function deletionBlockReason(AcreditacionCargo $cargo): string
    {
        if (! $this->acreditadosTableExists()) {
            return 'No se puede eliminar: es la última fila activa de este CARGO APO. Desactívela o cree otra fila activa del mismo APO.';
        }

        return 'No se puede eliminar: es la última fila activa de este CARGO APO y hay acreditados que lo usan. Desactívela en su lugar.';
    }

    /**
     * UPDATE de cargo_apo: bloquear si el valor anterior está referenciado
     * (cuando exista la tabla) y el cambio no es no-op.
     */
    public function canRenameCargoApo(AcreditacionCargo $cargo, string $newCargoApo): bool
    {
        if ($this->normalizeApo($cargo->cargo_apo) === $this->normalizeApo($newCargoApo)) {
            return true;
        }

        if (! $this->acreditadosTableExists()) {
            return true;
        }

        return ! $this->hasAcreditadosWithApo($cargo->cargo_apo);
    }

    public function renameBlockReason(): string
    {
        return 'No se puede cambiar el CARGO APO: hay acreditados que usan el valor actual. Edite Manager, Informe o Acreditación, o cree una fila nueva.';
    }

    public function isLastActiveForApo(AcreditacionCargo $cargo): bool
    {
        if (! $cargo->is_active) {
            return false;
        }

        $activeCount = AcreditacionCargo::query()
            ->active()
            ->forCargoApo($cargo->cargo_apo)
            ->count();

        return $activeCount <= 1;
    }

    public function hasAcreditadosWithApo(string $cargoApo): bool
    {
        if (! $this->acreditadosTableExists()) {
            return false;
        }

        if (! Schema::hasColumn(self::ACREDITADOS_TABLE, 'cargo_apo')) {
            return false;
        }

        return DB::table(self::ACREDITADOS_TABLE)
            ->whereRaw('LOWER(TRIM(cargo_apo)) = ?', [$this->normalizeApo($cargoApo)])
            ->exists();
    }

    public function acreditadosTableExists(): bool
    {
        return Schema::hasTable(self::ACREDITADOS_TABLE);
    }

    public function normalizeApo(string $cargoApo): string
    {
        return mb_strtolower(trim($cargoApo));
    }
}
