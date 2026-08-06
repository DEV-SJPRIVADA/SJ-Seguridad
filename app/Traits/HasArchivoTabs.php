<?php

namespace App\Traits;

trait HasArchivoTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function getArchivoSubTabs(string $activeTab): array
    {
        return [
            [
                'label' => 'Historias Laborales',
                'url' => route('gestion-humana.archivo.labor-histories.index'),
                'active' => $activeTab === 'historias-laborales',
            ],
            [
                'label' => 'Historial de consultas',
                'url' => route('gestion-humana.archivo.consultation-history.index'),
                'active' => $activeTab === 'historial-consultas',
            ],
        ];
    }
}
