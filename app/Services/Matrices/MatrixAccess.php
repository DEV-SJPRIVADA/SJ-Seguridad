<?php

namespace App\Services\Matrices;

/**
 * Acceso efectivo de un usuario a una matriz concreta.
 *
 * Se resuelve por unión de otorgamientos (usuario y área) y por propiedad
 * de la matriz. Es el único punto de verdad para permisos por registro.
 */
final readonly class MatrixAccess
{
    public function __construct(
        public bool $canView,
        public bool $canRegister,
        public bool $canViewOthers,
        public bool $canEditOthers,
        public bool $canManageDefinition,
    ) {}

    public static function none(): self
    {
        return new self(
            canView: false,
            canRegister: false,
            canViewOthers: false,
            canEditOthers: false,
            canManageDefinition: false,
        );
    }

    public static function owner(): self
    {
        return new self(
            canView: true,
            canRegister: true,
            canViewOthers: true,
            canEditOthers: true,
            canManageDefinition: true,
        );
    }

    public function canViewSubmission(bool $isCreator, bool $isAssignee): bool
    {
        if (! $this->canView) {
            return false;
        }

        return $this->canViewOthers || $isCreator || $isAssignee;
    }

    public function canEditSubmission(bool $isCreator, bool $isAssignee): bool
    {
        if (! $this->canView) {
            return false;
        }

        if ($this->canEditOthers) {
            return true;
        }

        return $this->canRegister && ($isCreator || $isAssignee);
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'can_view' => $this->canView,
            'can_register' => $this->canRegister,
            'can_view_others' => $this->canViewOthers,
            'can_edit_others' => $this->canEditOthers,
            'can_manage_definition' => $this->canManageDefinition,
        ];
    }
}
