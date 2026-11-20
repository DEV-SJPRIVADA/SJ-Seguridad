<?php

namespace App\Policies\Matrices;

use App\Models\Matrices\Matrix;
use App\Models\Matrices\MatrixSubmission;
use App\Models\User;
use App\Services\Matrices\MatrixAccessResolver;

class MatrixPolicy
{
    public function __construct(
        private readonly MatrixAccessResolver $accessResolver,
    ) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Matrix $matrix): bool
    {
        return $this->accessResolver->resolve($user, $matrix)->canView;
    }

    public function create(User $user): bool
    {
        return $user->can('matrices.manage');
    }

    public function updateDefinition(User $user, Matrix $matrix): bool
    {
        return $this->accessResolver->resolve($user, $matrix)->canManageDefinition;
    }

    public function share(User $user, Matrix $matrix): bool
    {
        return $this->accessResolver->resolve($user, $matrix)->canManageDefinition;
    }

    public function archive(User $user, Matrix $matrix): bool
    {
        return $this->accessResolver->resolve($user, $matrix)->canManageDefinition;
    }

    public function registerSubmission(User $user, Matrix $matrix): bool
    {
        $access = $this->accessResolver->resolve($user, $matrix);

        return ! $matrix->isArchived() && $access->canRegister;
    }

    public function viewSubmission(User $user, Matrix $matrix, MatrixSubmission $submission): bool
    {
        if ((int) $submission->matrix_id !== (int) $matrix->id) {
            return false;
        }

        return $this->accessResolver
            ->resolve($user, $matrix)
            ->canViewSubmission(
                isCreator: (int) $submission->created_by_user_id === (int) $user->id,
                isAssignee: (int) $submission->assigned_to_user_id === (int) $user->id,
            );
    }

    public function updateSubmission(User $user, Matrix $matrix, MatrixSubmission $submission): bool
    {
        if ((int) $submission->matrix_id !== (int) $matrix->id || $submission->isArchived()) {
            return false;
        }

        return $this->accessResolver
            ->resolve($user, $matrix)
            ->canEditSubmission(
                isCreator: (int) $submission->created_by_user_id === (int) $user->id,
                isAssignee: (int) $submission->assigned_to_user_id === (int) $user->id,
            );
    }

    public function archiveSubmission(User $user, Matrix $matrix, MatrixSubmission $submission): bool
    {
        if ((int) $submission->matrix_id !== (int) $matrix->id || $submission->isArchived()) {
            return false;
        }

        $access = $this->accessResolver->resolve($user, $matrix);

        if ($access->canManageDefinition) {
            return true;
        }

        return $access->canEditSubmission(
            isCreator: (int) $submission->created_by_user_id === (int) $user->id,
            isAssignee: (int) $submission->assigned_to_user_id === (int) $user->id,
        );
    }
}
