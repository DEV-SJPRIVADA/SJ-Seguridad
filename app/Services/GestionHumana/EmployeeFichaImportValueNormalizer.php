<?php

namespace App\Services\GestionHumana;

use App\Models\PayrollCatalogItem;

/**
 * Normaliza valores legibles de extractos (p. ej. nompr07) a códigos de nómina / catálogo.
 */
class EmployeeFichaImportValueNormalizer
{
    public function documentType(mixed $value): ?string
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }

        if (str_contains($raw, ' — ')) {
            $raw = trim(explode(' — ', $raw, 2)[0]);
        }

        $upper = mb_strtoupper($raw);
        $compact = str_replace([' ', '.', '-'], '', $upper);

        return match (true) {
            in_array($compact, ['C', 'CC', 'CEDULA', 'CEDULADECIUDADANIA', 'CEDULADECIUDADANÍA'], true) => 'C',
            in_array($compact, ['CE', 'CEDULADEEXTRANJERIA', 'CEDULADEEXTRANJERÍA', 'CEDULAEXTRANJERIA'], true) => 'CE',
            in_array($compact, ['N', 'NIT'], true) => 'N',
            in_array($compact, ['TI', 'TARJETADEIDENTIDAD', 'TARJETAIDENTIDAD'], true) => 'TI',
            in_array($compact, ['PT', 'PERMISOTEMPORAL', 'PPT', 'PEP'], true) => 'PT',
            default => $raw,
        };
    }

    /**
     * Normaliza sexo a M/F. Valores tipo «NO APLICA» / N/A → null (no forzar un código inválido).
     */
    public function sex(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || $raw === '.') {
            return null;
        }

        $upper = mb_strtoupper($raw);
        $compact = preg_replace('/[\s.\-\/\\\\_]+/u', '', $upper) ?? $upper;

        return match (true) {
            in_array($compact, ['NOAPLICA', 'NOAPLICABLE', 'NA', 'ND', 'SINDATO', 'SININFORMACION'], true) => null,
            in_array($compact, ['M', 'MASCULINO', 'MASC', 'HOMBRE', '1'], true) => 'M',
            in_array($compact, ['F', 'FEMENINO', 'FEM', 'MUJER', '2'], true) => 'F',
            // Compat: extractos que empiezan por M/F (Masculino / Femenino).
            str_starts_with($upper, 'M') => 'M',
            str_starts_with($upper, 'F') => 'F',
            default => mb_strlen($upper) <= 5 ? $upper : null,
        };
    }

    /**
     * true si el Excel trae un sexo usable o vacío explícito; false si es «NO APLICA» u otro no mapeable
     * (en actualización no debe pisar el valor existente).
     */
    public function sexShouldApply(mixed $value): bool
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || $raw === '.') {
            return true;
        }

        return $this->sex($value) !== null;
    }

    public function accountType(mixed $value): ?string
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }

        $upper = mb_strtoupper($raw);
        $compact = str_replace([' ', '.'], '', $upper);

        return match (true) {
            in_array($compact, ['1', 'AHORRO', 'AHORROS'], true) => '1',
            in_array($compact, ['2', 'CORRIENTE'], true) => '2',
            default => $this->resolveCatalogCode('account_type', $raw, preferNumeric: true) ?? $raw,
        };
    }

    public function riskLevel(mixed $value): ?string
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }

        $upper = mb_strtoupper($raw);

        if (preg_match('/\bN\s*([1-6])\b/u', $upper, $matches) === 1) {
            return 'N'.$matches[1];
        }

        if (preg_match('/RIESGO\s*([1-6])/u', $upper, $matches) === 1) {
            return 'N'.$matches[1];
        }

        if (preg_match('/^([1-6])$/', $upper, $matches) === 1) {
            return 'N'.$matches[1];
        }

        return $this->resolveCatalogCode('risk_level', $raw) ?? $raw;
    }

    public function salaryType(mixed $value): ?string
    {
        return $this->resolvePreferringShortCode('salary_type', $value);
    }

    public function contractType(mixed $value): ?string
    {
        return $this->resolvePreferringShortCode('contract_type', $value);
    }

    public function paymentMethod(mixed $value): ?string
    {
        return $this->resolvePreferringShortCode('payment_method', $value);
    }

    public function bank(mixed $value): ?string
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }

        return $this->resolveCatalogCode('bank', $raw) ?? $raw;
    }

    public function linkageType(mixed $value): ?string
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }

        return $this->resolveCatalogCode('linkage_type', $raw) ?? $raw;
    }

    private function resolvePreferringShortCode(string $catalogType, mixed $value): ?string
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }

        $byCode = PayrollCatalogItem::query()
            ->ofType($catalogType)
            ->where('code', $raw)
            ->value('code');

        if (is_string($byCode) && $byCode !== '') {
            return $byCode;
        }

        $resolved = $this->resolveCatalogCode($catalogType, $raw, preferNumeric: true);

        return $resolved ?? $raw;
    }

    private function resolveCatalogCode(string $catalogType, string $value, bool $preferNumeric = false): ?string
    {
        $needle = mb_strtolower(trim($value));

        $items = PayrollCatalogItem::query()
            ->ofType($catalogType)
            ->where(function ($query) use ($needle): void {
                $query->whereRaw('LOWER(code) = ?', [$needle])
                    ->orWhereRaw('LOWER(name) = ?', [$needle]);
            })
            ->get(['code', 'name']);

        if ($items->isEmpty()) {
            return null;
        }

        if ($preferNumeric) {
            $numeric = $items->first(
                fn (PayrollCatalogItem $item): bool => (bool) preg_match('/^\d+$/', (string) $item->code)
            );

            if ($numeric !== null) {
                return (string) $numeric->code;
            }
        }

        $exactCode = $items->first(
            fn (PayrollCatalogItem $item): bool => mb_strtolower((string) $item->code) === $needle
        );

        return (string) ($exactCode?->code ?? $items->first()?->code);
    }

    private function trimOrNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || $value === '.') {
            return null;
        }

        return $value;
    }
}
