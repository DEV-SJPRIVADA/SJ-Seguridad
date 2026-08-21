<?php

namespace App\Services\GestionHumana\TerminationLetter;

use App\Models\EmployeeFichaEmploymentPeriod;
use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisitionFichaEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TerminationLetterVariableBuilder
{
    /**
     * @return array<string, string>
     */
    public function build(
        EmployeeFichaEmploymentPeriod $period,
        PersonalRequisitionFichaEntry $entry,
        ?EmployeeFichaProfile $profile,
        ?CarbonInterface $letterDate = null,
        ?int $signatoryId = null,
    ): array {
        $letterDate ??= now();

        $firma = $this->resolveSignatory($signatoryId);

        $city = $period->work_center_name
            ?: $profile?->residence_city_name
            ?: '';

        return [
            'FECHA' => $this->formatLongDate($letterDate),
            'NOMBRE' => (string) $entry->hired_full_name,
            'CEDULA' => (string) $entry->hired_document,
            'CARGO' => (string) ($period->position_name ?? ''),
            'CIUDAD' => (string) $city,
            'FECHA_TERMINACION' => $this->formatShortDate($period->last_work_day ?? $period->termination_date),
            'FECHA_INGRESO' => $this->formatShortDate($period->hire_date),
            'SALARIO' => $this->formatSalary($period->salary),
            'TIPO_CONTRATO' => (string) ($period->contract_type_name ?? ''),
            'FIRMA' => $firma['name'],
            'CARGO_FIRMA' => $firma['code'],
        ];
    }

    private function resolveSignatory(?int $signatoryId): array
    {
        if ($signatoryId !== null) {
            $item = PayrollCatalogItem::query()
                ->where('id', $signatoryId)
                ->where('catalog_type', 'firmas')
                ->first();

            if ($item !== null) {
                return ['name' => (string) $item->name, 'code' => (string) $item->code];
            }
        }

        $fallback = config('employee_ficha.termination_letter_signatory', []);

        return ['name' => (string) ($fallback['name'] ?? ''), 'code' => (string) ($fallback['title'] ?? '')];
    }

    private function formatLongDate(?CarbonInterface $date): string
    {
        if ($date === null) {
            return '';
        }

        $carbon = Carbon::parse($date);
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $month = $months[(int) $carbon->format('n')] ?? $carbon->format('F');

        return sprintf('%d de %s de %d', (int) $carbon->format('j'), $month, (int) $carbon->format('Y'));
    }

    private function formatShortDate(mixed $date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    private function formatSalary(mixed $salary): string
    {
        if ($salary === null || $salary === '') {
            return '';
        }

        return '$ '.number_format((float) $salary, 0, ',', '.');
    }
}
