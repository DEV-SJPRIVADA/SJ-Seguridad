<?php

namespace App\Http\Controllers\Matrices\Concerns;

use App\Models\Matrices\Matrix;
use App\Models\User;
use App\Services\Matrices\MatrixListingService;

trait BuildsMatrixTabs
{
    /**
     * Pestañas del índice.
     *
     * - Quien administra (`matrices.manage`) ve sus matrices + «Nueva matriz».
     * - Quien solo fue compartido no ve el grid de tarjetas: «Mis matrices»
     *   abre la lista simple y luego una pestaña por cada matriz asignada.
     *
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function indexTabs(string $module, string $active = 'index'): array
    {
        $user = auth()->user();
        $listing = app(MatrixListingService::class);
        $isAdmin = $user instanceof User && $listing->canSeeAdminCards($user);

        $tabs = [
            [
                'label' => 'Mis matrices',
                'url' => $isAdmin
                    ? route('matrices.index', ['module' => $module])
                    : route('matrices.mine', ['module' => $module]),
                'active' => $active === 'index' || $active === 'mine',
            ],
        ];

        if ($isAdmin) {
            $tabs[] = [
                'label' => 'Nueva matriz',
                'url' => route('matrices.create', ['module' => $module]),
                'active' => $active === 'create',
            ];
        }

        return array_merge($tabs, $this->matrixTabsFor($module, $user, activeMatrixId: 0, activeTab: ''));
    }

    /**
     * Pestañas dentro de una matriz: sus matrices + la matriz actual + acciones.
     *
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    protected function matrixTabs(Matrix $matrix, string $module, string $active = 'records'): array
    {
        $user = auth()->user();
        $listing = app(MatrixListingService::class);
        $isAdmin = $user instanceof User && $listing->canSeeAdminCards($user);

        $tabs = [
            [
                'label' => 'Mis matrices',
                'url' => $isAdmin
                    ? route('matrices.index', ['module' => $module])
                    : route('matrices.mine', ['module' => $module]),
                'active' => false,
            ],
        ];

        $tabs = array_merge($tabs, $this->matrixTabsFor(
            $module,
            $user,
            activeMatrixId: (int) $matrix->id,
            activeTab: $active === 'records' ? 'records' : '',
        ));

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

    /**
     * Una pestaña por matriz propia o compartida. Solo marca activa la que se
     * está viendo; las demás navegan a su listado de registros.
     *
     * @return array<int, array{label: string, url: string, active: bool}>
     */
    private function matrixTabsFor(string $module, ?User $user, int $activeMatrixId, string $activeTab): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return app(MatrixListingService::class)
            ->tabbedMatrices($user)
            ->map(fn (Matrix $matrix): array => [
                'label' => (string) $matrix->name,
                'url' => route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]),
                'active' => $activeTab === 'records' && (int) $matrix->id === $activeMatrixId,
            ])
            ->values()
            ->all();
    }
}
