<?php

namespace App\Http\Controllers\Matrices\Concerns;

use App\Models\Matrices\Matrix;
use App\Models\User;

trait BuildsMatrixTabs
{
    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function indexTabs(string $module, string $active = 'index'): array
    {
        $tabs = [
            [
                'label' => 'Mis matrices',
                'url' => route('matrices.index', ['module' => $module]),
                'active' => $active === 'index',
            ],
        ];

        $user = auth()->user();

        if ($user instanceof User && $user->can('matrices.manage')) {
            $tabs[] = [
                'label' => 'Nueva matriz',
                'url' => route('matrices.create', ['module' => $module]),
                'active' => $active === 'create',
            ];
        }

        return $tabs;
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function matrixTabs(Matrix $matrix, string $module, string $active = 'records'): array
    {
        $tabs = [
            [
                'label' => 'Mis matrices',
                'url' => route('matrices.index', ['module' => $module]),
                'active' => false,
            ],
            [
                'label' => $matrix->name,
                'url' => route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]),
                'active' => $active === 'records',
            ],
        ];

        $user = auth()->user();

        if ($user instanceof User && $user->can('share', $matrix)) {
            $tabs[] = [
                'label' => 'Compartir',
                'url' => route('matrices.share.index', ['module' => $module, 'matrix' => $matrix->id]),
                'active' => $active === 'share',
            ];
            $tabs[] = [
                'label' => 'Definición',
                'url' => route('matrices.edit', ['module' => $module, 'matrix' => $matrix->id]),
                'active' => $active === 'definition',
            ];
        }

        return $tabs;
    }
}
