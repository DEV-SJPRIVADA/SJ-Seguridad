<?php

namespace Tests\Feature;

use App\Models\EmployeeFichaProfile;
use App\Models\PayrollCatalogItem;
use App\Models\PersonalRequisition;
use App\Models\PersonalRequisitionFichaEntry;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Services\Access\FichaEmpleadosAccessService;
use App\Services\Navigation\NavigationResolver;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FichaEmpleadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_personal_requisitions_table_has_hired_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('personal_requisitions', ['hired_document', 'hired_full_name']));
    }

    public function test_ficha_entries_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('personal_requisition_ficha_entries', [
            'id',
            'personal_requisition_id',
            'hired_document',
            'hired_full_name',
            'moved_to_ficha_at',
            'moved_to_ficha_by',
            'created_by',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_ficha_entry_belongs_to_requisition_and_delegates_context_accessors(): void
    {
        $requisition = $this->createRequisition('REQ-FICHA-0001');
        $recruiter = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '123456789',
            'hired_full_name' => 'Juan Perez',
            'created_by' => $recruiter->id,
        ]);

        $this->assertTrue($entry->requisition->is($requisition));
        $this->assertSame($requisition->id, $requisition->fichaEntry->id);
        $this->assertSame($requisition->code, $entry->requisitionCode());
        $this->assertSame($requisition->position->name, $entry->positionName());
        $this->assertSame($requisition->client->name, $entry->clientName());
        $this->assertSame($requisition->city->name, $entry->cityName());
        $this->assertTrue($entry->creator->is($recruiter));
        $this->assertNull($entry->movedBy);
    }

    public function test_scope_pending_and_in_ficha_filter_by_moved_to_ficha_at(): void
    {
        $pendingRequisition = $this->createRequisition('REQ-FICHA-0002');
        $inFichaRequisition = $this->createRequisition('REQ-FICHA-0003');
        $mover = User::factory()->create(['must_change_password' => false]);

        $pending = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $pendingRequisition->id,
            'hired_document' => '111111111',
            'hired_full_name' => 'Pendiente Uno',
        ]);

        $inFicha = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $inFichaRequisition->id,
            'hired_document' => '222222222',
            'hired_full_name' => 'En Ficha Uno',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $pendingIds = PersonalRequisitionFichaEntry::query()->pending()->pluck('id');
        $inFichaIds = PersonalRequisitionFichaEntry::query()->inFicha()->pluck('id');

        $this->assertTrue($pendingIds->contains($pending->id));
        $this->assertFalse($pendingIds->contains($inFicha->id));
        $this->assertTrue($inFichaIds->contains($inFicha->id));
        $this->assertFalse($inFichaIds->contains($pending->id));
    }

    public function test_personal_requisition_fillable_includes_hired_fields(): void
    {
        $requisition = $this->createRequisition('REQ-FICHA-0004', [
            'hired_document' => '333333333',
            'hired_full_name' => 'Contratado Fillable',
        ]);

        $this->assertDatabaseHas('personal_requisitions', [
            'id' => $requisition->id,
            'hired_document' => '333333333',
            'hired_full_name' => 'Contratado Fillable',
        ]);
    }

    public function test_admin_bypass_can_view_and_manage_ficha_empleados(): void
    {
        $service = app(FichaEmpleadosAccessService::class);

        $admin = User::factory()->create(['must_change_password' => false]);
        $admin->assignRole('usuario');
        $admin->givePermissionTo('manage.users');

        $this->assertTrue($service->canViewFichaEmpleadosBoard($admin));
        $this->assertTrue($service->canView($admin));
        $this->assertTrue($service->canManage($admin));
        $this->assertSame(['empleados'], $service->visibleTabsFor($admin));
    }

    public function test_user_without_permissions_cannot_view_or_manage_ficha_empleados(): void
    {
        $service = app(FichaEmpleadosAccessService::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');

        $this->assertFalse($service->canViewFichaEmpleadosBoard($user));
        $this->assertFalse($service->canView($user));
        $this->assertFalse($service->canManage($user));
        $this->assertSame([], $service->visibleTabsFor($user));
    }

    public function test_user_with_view_permission_can_view_but_not_manage(): void
    {
        $service = app(FichaEmpleadosAccessService::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo('ficha_empleados.view');

        $this->assertFalse($service->canViewFichaEmpleadosBoard($user));
        $this->assertTrue($service->canView($user));
        $this->assertFalse($service->canManage($user));
        $this->assertSame(['empleados'], $service->visibleTabsFor($user));
    }

    public function test_manage_permission_implies_view_permission(): void
    {
        $service = app(FichaEmpleadosAccessService::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo('ficha_empleados.manage');

        $this->assertTrue($service->canView($user));
        $this->assertTrue($service->canManage($user));
        $this->assertFalse($service->canViewFichaEmpleadosBoard($user));
    }

    public function test_board_permission_alone_grants_board_visibility_without_tab_access(): void
    {
        $service = app(FichaEmpleadosAccessService::class);

        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.ficha_empleados');

        $this->assertTrue($service->canViewFichaEmpleadosBoard($user));
        $this->assertFalse($service->canView($user));
        $this->assertFalse($service->canManage($user));
        $this->assertSame([], $service->visibleTabsFor($user));
    }

    public function test_ficha_empleados_permissions_are_independent_from_requisitions_gestion(): void
    {
        $service = app(FichaEmpleadosAccessService::class);

        $gestionUser = User::factory()->create(['must_change_password' => false]);
        $gestionUser->assignRole('usuario');
        $gestionUser->givePermissionTo('requisitions.tab.gestion');

        $this->assertFalse($service->canView($gestionUser));
        $this->assertFalse($service->canManage($gestionUser));

        $fichaUser = User::factory()->create(['must_change_password' => false]);
        $fichaUser->assignRole('usuario');
        $fichaUser->givePermissionTo('ficha_empleados.manage');

        $this->assertFalse($fichaUser->can('requisitions.tab.gestion'));
        $this->assertTrue($service->canManage($fichaUser));
    }

    public function test_ficha_empleados_index_forbidden_without_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');

        $response = $this->actingAs($user)->get(route('gestion-humana.ficha-empleados.employees.index'));

        $response->assertForbidden();
    }

    public function test_ficha_empleados_index_lists_en_ficha_by_default(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $pendingRequisition = $this->createRequisition('REQ-FICHA-1001');
        $inFichaRequisition = $this->createRequisition('REQ-FICHA-1002');
        $mover = User::factory()->create(['must_change_password' => false]);

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $pendingRequisition->id,
            'hired_document' => '900000001',
            'hired_full_name' => 'Pendiente Indice',
        ]);

        $inFicha = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $inFichaRequisition->id,
            'hired_document' => '900000002',
            'hired_full_name' => 'En Ficha Indice',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('gestion-humana.ficha-empleados.employees.index'));

        $response->assertOk();
        $response->assertViewHas('entries', function ($entries) use ($inFicha): bool {
            return $entries->pluck('id')->contains($inFicha->id) && $entries->count() === 1;
        });
    }

    public function test_ficha_empleados_index_pendientes_filter(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $pendingRequisition = $this->createRequisition('REQ-FICHA-1003');
        $inFichaRequisition = $this->createRequisition('REQ-FICHA-1004');
        $mover = User::factory()->create(['must_change_password' => false]);

        $pending = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $pendingRequisition->id,
            'hired_document' => '900000003',
            'hired_full_name' => 'Pendiente Filtro',
        ]);

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $inFichaRequisition->id,
            'hired_document' => '900000004',
            'hired_full_name' => 'En Ficha Filtro',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'pendientes']));

        $response->assertOk();
        $response->assertViewHas('entries', function ($entries) use ($pending): bool {
            return $entries->pluck('id')->contains($pending->id) && $entries->count() === 1;
        });
    }

    public function test_ficha_empleados_index_shows_hire_termination_and_status_columns(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $mover = User::factory()->create(['must_change_password' => false]);
        $activeRequisition = $this->createRequisition('REQ-FICHA-3001', [
            'hiring_date' => '2024-03-15',
        ]);
        $retiredRequisition = $this->createRequisition('REQ-FICHA-3002');

        $activeEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $activeRequisition->id,
            'hired_document' => '900000301',
            'hired_full_name' => 'Empleado Activo Tabla',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $retiredEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $retiredRequisition->id,
            'hired_document' => '900000302',
            'hired_full_name' => 'Empleado Retirado Tabla',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $activeEntry->id,
            'document_number' => '900000301',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
            'hire_date' => '2024-03-15',
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $retiredEntry->id,
            'document_number' => '900000302',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
            'hire_date' => '2020-01-10',
            'termination_date' => '2025-06-30',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.index', ['employment_status' => 'todos']));

        $response->assertOk()
            ->assertSee('Fecha contrato', false)
            ->assertSee('Fecha ingreso', false)
            ->assertSee('Fecha retiro', false)
            ->assertSee('Estado', false)
            ->assertDontSee('Agregado a ficha', false)
            ->assertSee('Empleado Activo Tabla')
            ->assertSee('Empleado Retirado Tabla')
            ->assertSee('Activo')
            ->assertSee('Desvinculado')
            ->assertSee('15/03/24')
            ->assertSee('30/06/25');
    }

    public function test_ficha_empleados_index_defaults_to_active_employment_status(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $mover = User::factory()->create(['must_change_password' => false]);

        $activeEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-3006')->id,
            'hired_document' => '900000306',
            'hired_full_name' => 'Default Activo',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $retiredEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-3007')->id,
            'hired_document' => '900000307',
            'hired_full_name' => 'Default Desvinculado',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $activeEntry->id,
            'document_number' => '900000306',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $retiredEntry->id,
            'document_number' => '900000307',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.index'))
            ->assertOk()
            ->assertViewHas('entries', function ($entries) use ($activeEntry, $retiredEntry): bool {
                $ids = $entries->pluck('id');

                return $ids->contains($activeEntry->id) && ! $ids->contains($retiredEntry->id);
            })
            ->assertViewHas('filters', fn (array $filters): bool => ($filters['employment_status_mode'] ?? '') === 'default_activo')
            ->assertSee('Default Activo')
            ->assertDontSee('Default Desvinculado');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.index', ['employment_status' => 'todos']))
            ->assertOk()
            ->assertViewHas('entries', function ($entries) use ($activeEntry, $retiredEntry): bool {
                $ids = $entries->pluck('id');

                return $ids->contains($activeEntry->id) && $ids->contains($retiredEntry->id);
            });
    }

    public function test_ficha_empleados_index_filters_by_employment_status(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $mover = User::factory()->create(['must_change_password' => false]);

        $activeEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-3003')->id,
            'hired_document' => '900000303',
            'hired_full_name' => 'Solo Activo Filtro',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $defaultActiveEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-3004')->id,
            'hired_document' => '900000304',
            'hired_full_name' => 'Activo Sin Perfil Filtro',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        $retiredEntry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-3005')->id,
            'hired_document' => '900000305',
            'hired_full_name' => 'Solo Desvinculado Filtro',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $activeEntry->id,
            'document_number' => '900000303',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $retiredEntry->id,
            'document_number' => '900000305',
            'employment_status' => EmployeeFichaProfile::STATUS_DESVINCULADO,
        ]);

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.index', ['employment_status' => 'desvinculado']))
            ->assertOk()
            ->assertViewHas('entries', function ($entries) use ($retiredEntry, $activeEntry, $defaultActiveEntry): bool {
                $ids = $entries->pluck('id');

                return $ids->contains($retiredEntry->id)
                    && ! $ids->contains($activeEntry->id)
                    && ! $ids->contains($defaultActiveEntry->id);
            })
            ->assertSee('Solo Desvinculado Filtro')
            ->assertDontSee('Solo Activo Filtro')
            ->assertDontSee('Activo Sin Perfil Filtro');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.index', ['employment_status' => 'activo']))
            ->assertOk()
            ->assertViewHas('entries', function ($entries) use ($retiredEntry, $activeEntry, $defaultActiveEntry): bool {
                $ids = $entries->pluck('id');

                return $ids->contains($activeEntry->id)
                    && $ids->contains($defaultActiveEntry->id)
                    && ! $ids->contains($retiredEntry->id);
            });
    }

    public function test_ficha_empleados_board_hidden_without_board_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.gestion');

        $navigation = app(NavigationResolver::class)->resolve($user, 'dashboard', 'gestion_humana');

        $gestionHumanaModule = $navigation['appNavigation']->firstWhere('key', 'gestion_humana');
        $boardKeys = collect($gestionHumanaModule['items'] ?? [])->pluck('label');

        $this->assertFalse($boardKeys->contains(config('access.boards.ficha_empleados')));
    }

    public function test_ficha_empleados_board_visible_with_board_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.gestion_humana.ficha_empleados');

        $navigation = app(NavigationResolver::class)->resolve($user, 'dashboard', 'gestion_humana');

        $gestionHumanaModule = $navigation['appNavigation']->firstWhere('key', 'gestion_humana');
        $boardLabels = collect($gestionHumanaModule['items'] ?? [])->pluck('label');

        $this->assertTrue($boardLabels->contains(config('access.boards.ficha_empleados')));
    }

    public function test_ficha_empleados_export_returns_plantilla_masivos_for_en_ficha_active(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $inFichaRequisition = $this->createRequisition('REQ-FICHA-2002');
        $mover = User::factory()->create(['must_change_password' => false]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $inFichaRequisition->id,
            'hired_document' => '900000008',
            'hired_full_name' => 'En Ficha Export',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $mover->id,
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '900000008',
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.export'));

        $response->assertOk();
        $response->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString(
            'plantilla_masivos_',
            $response->headers->get('content-disposition')
        );
    }

    public function test_ficha_empleados_export_forbidden_without_view_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('usuario');

        $response = $this->actingAs($user)->get(route('gestion-humana.ficha-empleados.employees.export'));

        $response->assertForbidden();
    }

    public function test_manual_employee_create_form_requires_manage_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertForbidden();
    }

    public function test_manual_employee_create_form_shows_document_type_select_from_catalog(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create'))
            ->assertOk()
            ->assertSee('id="document_type"', false)
            ->assertSee('C — Cedula')
            ->assertSee('TI — Tarjeta de Identidad');
    }

    public function test_manual_employee_create_stores_entry_without_requisition(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $response = $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), [
            'hired_document' => '801234567',
            'hired_full_name' => 'Empleado Manual Prueba',
            'document_type' => 'C',
            'sex' => 'M',
            'salary' => '2500000',
        ]);

        $entry = PersonalRequisitionFichaEntry::query()
            ->where('hired_document', '801234567')
            ->first();

        $this->assertNotNull($entry);
        $response->assertRedirect(route('gestion-humana.ficha-empleados.employees.ficha.edit', $entry));
        $this->assertNull($entry->personal_requisition_id);
        $this->assertNotNull($entry->moved_to_ficha_at);
        $this->assertTrue($entry->isManualEntry());
        $this->assertSame('Empleado Manual Prueba', $entry->profile?->full_name);
    }

    public function test_manual_employee_create_rejects_duplicate_document(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => null,
            'hired_document' => '809999999',
            'hired_full_name' => 'Ya Existe',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), [
                'hired_document' => '809999999',
                'hired_full_name' => 'Duplicado',
            ])
            ->assertSessionHasErrors('hired_document');
    }

    public function test_create_form_prefills_from_pending_ficha_entry_without_persisting(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4001', [
            'hired_document' => '900000401',
            'hired_full_name' => 'Prefill Sin Persistir',
            'base_salary' => 2000000,
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000401',
            'hired_full_name' => 'Prefill Sin Persistir',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]));

        $response->assertOk()
            ->assertSee('Gestionar empleado')
            ->assertSee('900000401')
            ->assertSee('Prefill Sin Persistir');

        $this->assertDatabaseMissing('employee_ficha_profiles', [
            'personal_requisition_ficha_entry_id' => $entry->id,
        ]);
        $this->assertNull($entry->fresh()->moved_to_ficha_at);
    }

    public function test_create_form_reuses_existing_profile_when_already_present(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4002', [
            'hired_document' => '900000402',
            'hired_full_name' => 'Con Perfil Previo',
            'base_salary' => 2000000,
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000402',
            'hired_full_name' => 'Con Perfil Previo',
        ]);

        EmployeeFichaProfile::query()->create([
            'personal_requisition_ficha_entry_id' => $entry->id,
            'document_number' => '900000402',
            'full_name' => 'Con Perfil Previo',
            'salary' => 5550000,
            'employment_status' => EmployeeFichaProfile::STATUS_ACTIVO,
        ]);

        $response = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]));

        $response->assertOk()->assertSee('5550000');

        $this->assertDatabaseCount('employee_ficha_profiles', 1);
    }

    public function test_create_form_returns_404_when_desde_entry_already_in_ficha(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4003');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000403',
            'hired_full_name' => 'Ya En Ficha',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]))
            ->assertNotFound();
    }

    public function test_create_form_returns_404_when_desde_entry_does_not_exist(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.create', ['desde' => 999999]))
            ->assertNotFound();
    }

    public function test_store_with_ficha_entry_id_updates_existing_entry_and_moves_to_ficha(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4004');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000404',
            'hired_full_name' => 'Gestionar Ahora',
        ]);

        $response = $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), [
            'ficha_entry_id' => $entry->id,
            'hired_document' => '900000404',
            'hired_full_name' => 'Gestionar Ahora Corregido',
            'document_type' => 'C',
            'sex' => 'F',
            'salary' => '3000000',
        ]);

        $this->assertDatabaseCount('personal_requisition_ficha_entries', 1);

        $entry->refresh();
        $this->assertNotNull($entry->moved_to_ficha_at);
        $this->assertTrue($entry->movedBy->is($manager));
        $this->assertSame('Gestionar Ahora Corregido', $entry->hired_full_name);
        $this->assertSame('Gestionar Ahora Corregido', $entry->profile?->full_name);

        $response->assertSessionHasNoErrors();
    }

    public function test_store_with_ficha_entry_id_redirects_to_index_en_ficha(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4005');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000405',
            'hired_full_name' => 'Redirect Test',
        ]);

        $response = $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), [
            'ficha_entry_id' => $entry->id,
            'hired_document' => '900000405',
            'hired_full_name' => 'Redirect Test',
        ]);

        $response->assertRedirect(route('gestion-humana.ficha-empleados.employees.index'));
    }

    public function test_store_with_ficha_entry_id_does_not_update_requisition_hired_fields(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4006', [
            'hired_document' => '900000406',
            'hired_full_name' => 'Nombre Original Requisicion',
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000406',
            'hired_full_name' => 'Nombre Original Requisicion',
        ]);

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), [
            'ficha_entry_id' => $entry->id,
            'hired_document' => '900000499',
            'hired_full_name' => 'Nombre Corregido En Ficha',
        ]);

        $requisition->refresh();
        $this->assertSame('900000406', $requisition->hired_document);
        $this->assertSame('Nombre Original Requisicion', $requisition->hired_full_name);
    }

    public function test_store_with_ficha_entry_id_allows_keeping_same_hired_document(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4007');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000407',
            'hired_full_name' => 'Mantiene Cedula',
        ]);

        $this->actingAs($manager)->post(route('gestion-humana.ficha-empleados.employees.store'), [
            'ficha_entry_id' => $entry->id,
            'hired_document' => '900000407',
            'hired_full_name' => 'Mantiene Cedula',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($entry->fresh()->moved_to_ficha_at);
    }

    public function test_store_with_ficha_entry_id_rejects_document_duplicated_in_other_entry(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-4008')->id,
            'hired_document' => '900000408',
            'hired_full_name' => 'Ya En Ficha Con Cedula',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
        ]);

        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $this->createRequisition('REQ-FICHA-4009')->id,
            'hired_document' => '900000409',
            'hired_full_name' => 'Pendiente Con Cedula Propia',
        ]);

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), [
                'ficha_entry_id' => $entry->id,
                'hired_document' => '900000408',
                'hired_full_name' => 'Pendiente Con Cedula Propia',
            ])
            ->assertSessionHasErrors('hired_document');

        $this->assertNull($entry->fresh()->moved_to_ficha_at);
    }

    public function test_store_with_ficha_entry_id_rejects_when_entry_already_moved(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4010');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000410',
            'hired_full_name' => 'Ya Movido Doble Envio',
            'moved_to_ficha_at' => now(),
            'moved_to_ficha_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.employees.store'), [
                'ficha_entry_id' => $entry->id,
                'hired_document' => '900000410',
                'hired_full_name' => 'Ya Movido Doble Envio',
            ])
            ->assertSessionHasErrors('ficha_entry_id');
    }

    public function test_pending_index_shows_gestionar_empleado_button_linking_to_create_with_desde(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $requisition = $this->createRequisition('REQ-FICHA-4011');
        $entry = PersonalRequisitionFichaEntry::query()->create([
            'personal_requisition_id' => $requisition->id,
            'hired_document' => '900000411',
            'hired_full_name' => 'Pendiente Boton Vista',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'pendientes']));

        $response->assertOk()
            ->assertSee('Gestionar Empleado')
            ->assertDontSee('Agregar a ficha empleados')
            ->assertSee(route('gestion-humana.ficha-empleados.employees.create', ['desde' => $entry->id]), false);
    }

    public function test_catalogs_tab_visible_only_for_manage_users(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $viewerTabs = app(FichaEmpleadosAccessService::class)->visibleTabsFor($viewer);
        $managerTabs = app(FichaEmpleadosAccessService::class)->visibleTabsFor($manager);

        $this->assertNotContains('catalogos', $viewerTabs);
        $this->assertContains('catalogos', $managerTabs);
    }

    public function test_catalogs_index_forbidden_without_manage_permission(): void
    {
        $viewer = User::factory()->create(['must_change_password' => false]);
        $viewer->assignRole('usuario');
        $viewer->givePermissionTo('ficha_empleados.view');

        $this->actingAs($viewer)
            ->get(route('gestion-humana.ficha-empleados.catalogs.index'))
            ->assertForbidden();
    }

    public function test_catalogs_crud_for_manager(): void
    {
        $manager = User::factory()->create(['must_change_password' => false]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo('ficha_empleados.manage');

        $this->actingAs($manager)
            ->get(route('gestion-humana.ficha-empleados.catalogs.index'))
            ->assertOk()
            ->assertSee('Catalogos de empleados')
            ->assertSee('Tipo documento')
            ->assertSee('EPS');

        $this->actingAs($manager)
            ->post(route('gestion-humana.ficha-empleados.catalogs.store', ['type' => 'eps']), [
                'code' => 'EPS-TEST',
                'name' => 'EPS Prueba UI',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.catalogs.index').'#section-eps');

        $item = PayrollCatalogItem::query()
            ->where('catalog_type', 'eps')
            ->where('code', 'EPS-TEST')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame('EPS Prueba UI', $item->name);

        $this->actingAs($manager)
            ->patch(route('gestion-humana.ficha-empleados.catalogs.update', ['type' => 'eps', 'item' => $item->id]), [
                'code' => 'EPS-TEST',
                'name' => 'EPS Prueba Actualizada',
                'sort_order' => 10,
                'is_active' => '1',
            ])
            ->assertRedirect(route('gestion-humana.ficha-empleados.catalogs.index').'#section-eps');

        $this->assertSame('EPS Prueba Actualizada', $item->fresh()->name);

        $this->actingAs($manager)
            ->delete(route('gestion-humana.ficha-empleados.catalogs.destroy', ['type' => 'eps', 'item' => $item->id]))
            ->assertRedirect(route('gestion-humana.ficha-empleados.catalogs.index').'#section-eps');

        $this->assertDatabaseMissing('payroll_catalog_items', ['id' => $item->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRequisition(string $code, array $overrides = []): PersonalRequisition
    {
        $requester = User::factory()->create(['must_change_password' => false]);

        return PersonalRequisition::query()->create(array_merge([
            'code' => $code,
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'gestion_humana',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'gestion_humana',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => RequisitionClientType::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil de prueba para Ficha empleados.',
            'service_structure' => 'Turno de prueba para Ficha empleados.',
            'cost_center' => 'CC-FICHA',
            'status' => PersonalRequisition::STATUS_CONTRATADO,
            'status_changed_at' => now(),
        ], $overrides));
    }
}
