<?php

namespace App\Services\Indicadores;

use Illuminate\Support\Str;

class IndicatorCapturePdfPresenter
{
    public function __construct(
        private readonly IndicatorPdfChartRenderer $chartRenderer,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function present(array $context, bool $consolidadoPortrait = false): array
    {
        if ($consolidadoPortrait) {
            $context['pdfPortrait'] = true;
            $context['pdfSplitBeforeAnalysis'] = true;
            $context['pdfOmitChartTitle'] = true;
            $context['pdfChartWidth'] = 540;
            $context['pdfChartHeight'] = 300;
            $context['pdfPieWidth'] = 280;
            $context['pdfPieHeight'] = 220;
        }

        return array_merge($context, [
            'pdfMode' => true,
            'readOnly' => true,
            'pdfOmitChartTitle' => $context['pdfOmitChartTitle'] ?? true,
            'chartImages' => $this->chartRenderer->buildChartImages(array_merge($context, [
                'pdfOmitChartTitle' => $context['pdfOmitChartTitle'] ?? true,
            ])),
            'logoPath' => public_path('images/logoSj.png'),
            'formFieldRows' => $this->buildFormFieldRows($context),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{label: string, value: string}>
     */
    private function buildFormFieldRows(array $context): array
    {
        $form = (array) ($context['form'] ?? []);
        $rows = [];

        foreach ($form as $key => $value) {
            if ($key === 'clasificacion_por_tipo') {
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            $rows[] = [
                'label' => $this->humanizeFieldKey((string) $key),
                'value' => (string) $value,
            ];
        }

        foreach ((array) ($form['clasificacion_por_tipo'] ?? []) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = trim((string) ($item['tipo'] ?? ''));
            $qty = (string) ($item['cantidad'] ?? '');

            if ($type === '' && $qty === '') {
                continue;
            }

            $rows[] = [
                'label' => 'Clasificacion '.((int) $index + 1),
                'value' => trim($type.' — Cantidad: '.$qty),
            ];
        }

        return $rows;
    }

    private function humanizeFieldKey(string $key): string
    {
        return Str::of($key)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
