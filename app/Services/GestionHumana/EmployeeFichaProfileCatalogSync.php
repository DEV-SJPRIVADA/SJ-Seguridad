<?php

namespace App\Services\GestionHumana;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;

class EmployeeFichaProfileCatalogSync
{
    public function sync(EmployeeFichaProfile $profile): EmployeeFichaProfile
    {
        $this->syncProfileCodeNamePairs($profile);
        $this->syncPayrollExtraCodeNamePairs($profile);
        $this->syncParsedNameParts($profile);
        $this->normalizeDocumentTypeCode($profile);

        return $profile;
    }

    public function syncAndSave(EmployeeFichaProfile $profile): EmployeeFichaProfile
    {
        $this->sync($profile);
        $profile->save();

        return $profile;
    }

    private function syncProfileCodeNamePairs(EmployeeFichaProfile $profile): void
    {
        /** @var list<array{code: string, name: string, type: string}> $pairs */
        $pairs = config('employee_ficha.catalog_profile_code_name_pairs', []);

        foreach ($pairs as $pair) {
            $code = $profile->{$pair['code']} ?? null;

            if ($code === null || $code === '') {
                continue;
            }

            $name = $this->catalogName($pair['type'], (string) $code);

            if ($name !== null) {
                $profile->{$pair['name']} = $name;
            }
        }
    }

    private function syncPayrollExtraCodeNamePairs(EmployeeFichaProfile $profile): void
    {
        /** @var list<array{code: string, name: string, type: string, target: string}> $pairs */
        $pairs = config('employee_ficha.catalog_payroll_extra_code_name_pairs', []);
        $extra = is_array($profile->payroll_extra) ? $profile->payroll_extra : [];

        foreach ($pairs as $pair) {
            $code = data_get($extra, $pair['code']);

            if ($code === null || $code === '') {
                continue;
            }

            $name = $this->catalogName($pair['type'], (string) $code);

            if ($name === null) {
                continue;
            }

            if (($pair['target'] ?? 'payroll_extra') === 'profile') {
                $profile->{$pair['name']} = $name;
            } else {
                data_set($extra, $pair['name'], $name);
            }
        }

        $profile->payroll_extra = $extra;
    }

    private function syncParsedNameParts(EmployeeFichaProfile $profile): void
    {
        $fullName = trim((string) $profile->full_name);

        if ($fullName === '') {
            return;
        }

        $parsed = EmployeeFichaNameParser::parse($fullName);

        $profile->fill([
            'full_name' => $parsed['full_name'] ?: $fullName,
            'first_surname' => $parsed['first_surname'],
            'second_surname' => $parsed['second_surname'],
            'first_name' => $parsed['first_name'],
            'second_name' => $parsed['second_name'],
        ]);
    }

    private function normalizeDocumentTypeCode(EmployeeFichaProfile $profile): void
    {
        $value = trim((string) ($profile->document_type ?? ''));

        if ($value === '') {
            return;
        }

        if (str_contains($value, ' — ')) {
            $profile->document_type = trim(explode(' — ', $value, 2)[0]);

            return;
        }

        $profile->document_type = $value;
    }

    private function catalogName(string $catalogType, string $code): ?string
    {
        return PayrollCatalogItem::query()
            ->ofType($catalogType)
            ->where('code', $code)
            ->value('name');
    }
}
