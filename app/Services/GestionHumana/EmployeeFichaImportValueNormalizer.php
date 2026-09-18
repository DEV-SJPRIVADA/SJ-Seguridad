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

    public function sex(mixed $value): ?string
    {
        $value = mb_strtoupper(trim((string) ($value ?? '')));

        return match (true) {
            $value === '' => null,
            str_starts_with($value, 'M') => 'M',
            str_starts_with($value, 'F') => 'F',
            default => $value,
        };
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
