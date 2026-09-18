<?php

namespace App\Policies;

use App\Models\DevelopmentRequest;
use App\Models\User;
use App\Services\Access\DevelopmentRequestAccessService;

class DevelopmentRequestPolicy
{
    public function __construct(
        private readonly DevelopmentRequestAccessService $access,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->access->canView($user);
    }

    public function view(User $user, DevelopmentRequest $developmentRequest): bool
    {
        if ($this->access->isAdminBypass($user)) {
            return true;
        }

        if ($this->access->canProcessTic($user)) {
            return true;
        }

        if ((int) $developmentRequest->created_by === (int) $user->id) {
            return $this->access->canViewOwn($user) || $this->access->canCreate($user);
        }

        if ((int) $developmentRequest->leader_id === (int) $user->id) {
            return $this->access->canApproveLeader($user);
        }

        return $this->access->canView($user) && $user->can('devreq.tab.view');
    }

    public function create(User $user): bool
    {
        return $this->access->canCreate($user);
    }

    public function update(User $user, DevelopmentRequest $developmentRequest): bool
    {
        if ($this->access->isAdminBypass($user)) {
            return true;
        }

        if ($this->access->canProcessTic($user)) {
            return true;
        }

        if ((int) $developmentRequest->created_by !== (int) $user->id) {
            return false;
        }

        if (! in_array($developmentRequest->status, [
            DevelopmentRequest::STATUS_BORRADOR,
            DevelopmentRequest::STATUS_DEVUELTO,
        ], true)) {
            return false;
        }

        return $this->access->canCreate($user) || $this->access->canViewOwn($user);
    }

    /**
     * Hilo de conversacion (T3b). Bloqueado en cerrado/rechazado.
     */
    public function comment(User $user, DevelopmentRequest $developmentRequest): bool
    {
        if ($developmentRequest->isConversationClosed()) {
            return false;
        }

        return $this->view($user, $developmentRequest);
    }
}
