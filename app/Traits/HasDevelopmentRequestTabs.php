<?php

namespace App\Traits;

use App\Models\DevelopmentRequest;
use Illuminate\Support\Collection;

trait HasDevelopmentRequestTabs
{
    protected function getDevelopmentRequestSubTabs(string $module): Collection
    {
        $user = auth()->user();
        $tabs = $user?->developmentRequestBoardTabsFor($module) ?? collect();
        $routeName = request()->route()?->getName();
        $showContext = $this->resolveDevelopmentRequestShowTabContext();

        return $tabs->map(function ($tab) use ($module, $routeName, $showContext) {
            $targetRoute = match ($tab) {
                'nueva' => 'development-requests.create',
                'mis_solicitudes' => 'development-requests.my-requests',
                'aprobacion_lider' => 'development-requests.leader-approval',
                'bandeja_tic' => 'development-requests.tic-queue',
                default => 'development-requests.index',
            };

            $active = match ($tab) {
                'nueva' => in_array($routeName, ['development-requests.create', 'development-requests.store'], true),
                'mis_solicitudes' => in_array($routeName, [
                    'development-requests.my-requests',
                    'development-requests.edit',
                    'development-requests.update',
                ], true) || ($routeName === 'development-requests.show' && $showContext === 'mis_solicitudes'),
                'aprobacion_lider' => str_starts_with((string) $routeName, 'development-requests.leader')
                    || ($routeName === 'development-requests.show' && $showContext === 'leader_approval'),
                'bandeja_tic' => str_starts_with((string) $routeName, 'development-requests.tic')
                    || ($routeName === 'development-requests.show' && $showContext === 'tic_queue'),
                default => false,
            };

            return [
                'label' => config("access.devreq_tabs.{$tab}", ucfirst(str_replace('_', ' ', (string) $tab))),
                'url' => route($targetRoute, ['module' => $module]),
                'active' => $active,
            ];
        });
    }

    /**
     * @return 'mis_solicitudes'|'leader_approval'|'tic_queue'|null
     */
    protected function resolveDevelopmentRequestShowTabContext(): ?string
    {
        $from = request()->query('from');

        if (in_array($from, ['mis_solicitudes', 'leader_approval', 'tic_queue'], true)) {
            return $from;
        }

        $developmentRequest = request()->route('development_request');
        $user = auth()->user();

        if (! $developmentRequest instanceof DevelopmentRequest || $user === null) {
            return null;
        }

        if ((int) $developmentRequest->created_by === (int) $user->id) {
            return 'mis_solicitudes';
        }

        if ((int) $developmentRequest->leader_id === (int) $user->id) {
            return 'leader_approval';
        }

        if ($user->can('devreq.tab.tic_queue') || $user->hasRole('super-admin')) {
            return 'tic_queue';
        }

        return 'mis_solicitudes';
    }
}
