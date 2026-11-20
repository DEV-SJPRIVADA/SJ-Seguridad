<?php

namespace Tests\Unit\Matrices;

use App\Services\Matrices\MatrixAccess;
use PHPUnit\Framework\TestCase;

class MatrixAccessTest extends TestCase
{
    public function test_owner_can_view_and_edit_every_submission(): void
    {
        $access = MatrixAccess::owner();

        $this->assertTrue($access->canViewSubmission(isCreator: false, isAssignee: false));
        $this->assertTrue($access->canEditSubmission(isCreator: false, isAssignee: false));
        $this->assertTrue($access->canManageDefinition);
    }

    public function test_none_cannot_view_or_edit_anything(): void
    {
        $access = MatrixAccess::none();

        $this->assertFalse($access->canViewSubmission(isCreator: true, isAssignee: true));
        $this->assertFalse($access->canEditSubmission(isCreator: true, isAssignee: true));
    }

    public function test_view_only_grant_cannot_edit_own_submission(): void
    {
        $access = new MatrixAccess(
            canView: true,
            canRegister: false,
            canViewOthers: false,
            canEditOthers: false,
            canManageDefinition: false,
        );

        $this->assertTrue($access->canViewSubmission(isCreator: true, isAssignee: false));
        $this->assertFalse($access->canEditSubmission(isCreator: true, isAssignee: false));
    }

    public function test_register_grant_can_edit_own_but_not_others(): void
    {
        $access = new MatrixAccess(
            canView: true,
            canRegister: true,
            canViewOthers: false,
            canEditOthers: false,
            canManageDefinition: false,
        );

        $this->assertTrue($access->canEditSubmission(isCreator: true, isAssignee: false));
        $this->assertTrue($access->canEditSubmission(isCreator: false, isAssignee: true));
        $this->assertFalse($access->canEditSubmission(isCreator: false, isAssignee: false));
        $this->assertFalse($access->canViewSubmission(isCreator: false, isAssignee: false));
    }

    public function test_view_others_grant_can_view_but_not_edit_foreign_submissions(): void
    {
        $access = new MatrixAccess(
            canView: true,
            canRegister: false,
            canViewOthers: true,
            canEditOthers: false,
            canManageDefinition: false,
        );

        $this->assertTrue($access->canViewSubmission(isCreator: false, isAssignee: false));
        $this->assertFalse($access->canEditSubmission(isCreator: false, isAssignee: false));
    }

    public function test_edit_others_grant_can_edit_foreign_submissions(): void
    {
        $access = new MatrixAccess(
            canView: true,
            canRegister: true,
            canViewOthers: true,
            canEditOthers: true,
            canManageDefinition: false,
        );

        $this->assertTrue($access->canEditSubmission(isCreator: false, isAssignee: false));
        $this->assertTrue($access->canViewSubmission(isCreator: false, isAssignee: false));
    }
}
