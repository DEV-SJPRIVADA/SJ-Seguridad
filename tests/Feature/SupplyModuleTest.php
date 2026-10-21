<?php

namespace Tests\Feature;

use App\Mail\SupplyRequestNotification;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\Supplies\SupplyPurchaseReportExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupplyModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_supply_catalog_is_seeded(): void
    {
        $this->assertGreaterThan(0, SupplyProduct::query()->count());
    }

    public function test_user_can_create_supply_request(): void
    {
        $user = $this->requester('operaciones');
        $product = SupplyProduct::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('supplies.store', ['module' => 'operaciones']), [
            'observations' => 'Pedido mensual de aseo.',
            'items' => [
                [
                    'type' => 'catalog',
                    'product_id' => $product->id,
                    'current_inventory' => 2,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertRedirect(route('supplies.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_requests', [
            'user_id' => $user->id,
            'area_key' => 'operaciones',
            'status' => 'pendiente_calidad',
            'sede_id' => $user->sede_id,
            'site_utilization' => $user->site?->utilization,
            'site_city' => $user->site?->city,
            'observations' => 'PEDIDO MENSUAL DE ASEO.',
        ]);
        $this->assertDatabaseHas('supply_request_items', [
            'supply_product_id' => $product->id,
            'requested_quantity' => 10,
            'current_inventory' => 2,
            'is_not_in_catalog' => false,
        ]);
    }

    public function test_user_can_create_supply_request_with_custom_item(): void
    {
        $user = $this->requester('operaciones');
        $product = SupplyProduct::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('supplies.store', ['module' => 'operaciones']), [
            'items' => [
                [
                    'type' => 'catalog',
                    'product_id' => $product->id,
                    'current_inventory' => 1,
                    'quantity' => 3,
                ],
                [
                    'type' => 'custom',
                    'custom_name' => 'Guantes especiales talla XL',
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertRedirect(route('supplies.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_request_items', [
            'custom_product_name' => 'GUANTES ESPECIALES TALLA XL',
            'is_not_in_catalog' => true,
            'requested_quantity' => 2,
        ]);
    }

    public function test_owner_can_view_its_own_supply_request(): void
    {
        $user = $this->requester('operaciones');
        $request = $this->supplyRequest($user, 'operaciones');

        $response = $this->actingAs($user)->get(route('supplies.show', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]));

        $response->assertOk();
    }

    public function test_non_owner_without_review_access_cannot_view_supply_request(): void
    {
        $owner = $this->requester('operaciones');
        $intruder = $this->requester('operaciones');
        $request = $this->supplyRequest($owner, 'operaciones');

        $response = $this->actingAs($intruder)->get(route('supplies.show', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]));

        $response->assertForbidden();
    }

    public function test_approval_reviewer_can_open_approval_form_for_any_supply_request(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('operaciones');

        $request = $this->supplyRequest($owner, 'operaciones');

        $response = $this->actingAs($reviewer)->get(route('supplies.approval.edit', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]));

        $response->assertOk();
    }

    public function test_approval_can_approve_request_and_set_approved_quantities(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('operaciones');

        $request = $this->supplyRequest($owner, 'operaciones');
        $item = $request->items()->firstOrFail();

        $response = $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'quality_observations' => 'Aprobado con ajuste.',
            'items' => [
                $item->id => ['approved_quantity' => 6],
            ],
        ]);

        $response->assertRedirect(route('supplies.approval.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_requests', [
            'id' => $request->id,
            'status' => 'aprobada_calidad',
            'quality_reviewer_id' => $reviewer->id,
            'quality_observations' => 'APROBADO CON AJUSTE.',
        ]);
        $this->assertDatabaseHas('supply_request_items', [
            'id' => $item->id,
            'approved_quantity' => 6,
        ]);
    }

    public function test_approval_requires_valid_action_and_quantities(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('operaciones');
        $request = $this->supplyRequest($owner, 'operaciones');
        $item = $request->items()->firstOrFail();

        $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'otra',
        ])->assertSessionHasErrors('action');

        $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'items' => [$item->id => ['approved_quantity' => -1]],
        ])->assertSessionHasErrors("items.{$item->id}.approved_quantity");

        $this->assertSame('pendiente_calidad', $request->fresh()->status);
    }

    public function test_approval_reject_saves_observations_in_uppercase(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('operaciones');
        $request = $this->supplyRequest($owner, 'operaciones');

        $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'reject',
            'quality_observations' => 'falta justificar cantidades',
        ])->assertRedirect(route('supplies.approval.index', ['module' => 'operaciones']));

        $request->refresh();
        $this->assertSame('rechazada_calidad', $request->status);
        $this->assertSame('FALTA JUSTIFICAR CANTIDADES', $request->quality_observations);
    }

    public function test_approval_can_approve_custom_item(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('operaciones');

        $request = SupplyRequest::create([
            'user_id' => $owner->id,
            'area_key' => 'operaciones',
            'status' => 'pendiente_calidad',
        ]);

        $item = $request->items()->create([
            'custom_product_name' => 'Producto especial',
            'is_not_in_catalog' => true,
            'current_inventory' => 0,
            'requested_quantity' => 4,
        ]);

        $response = $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'items' => [
                $item->id => ['approved_quantity' => 3],
            ],
        ]);

        $response->assertRedirect(route('supplies.approval.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('supply_request_items', [
            'id' => $item->id,
            'approved_quantity' => 3,
        ]);
    }

    public function test_approval_cannot_reprocess_an_already_processed_request(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('calidad');

        $request = $this->supplyRequest($owner, 'operaciones', 'aprobada_calidad');
        $item = $request->items()->firstOrFail();

        $response = $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'operaciones',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'items' => [
                $item->id => ['approved_quantity' => 1],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_purchasing_routes_are_removed(): void
    {
        $user = $this->requester('compras');
        $user->givePermissionTo('supply.tab.catalog');

        $this->actingAs($user)
            ->get('/supplies/compras/gestion-compras')
            ->assertNotFound();
    }

    public function test_purchasing_tab_is_not_available(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.compras.suministros',
            'supply.tab.catalog',
        ]);

        $tabs = $user->supplyBoardTabsFor('compras');

        $this->assertFalse($tabs->contains('gestion_compras'));
        $this->assertFalse($tabs->contains('catalogo'));
        $this->assertSame(['insumos'], $user->catalogBoardTabs()->all());
    }

    public function test_approval_index_lists_pending_requests_from_all_areas_with_area_filter(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('calidad');

        $pendingOperaciones = $this->supplyRequest($owner, 'operaciones');
        $comercialPending = $this->supplyRequest($owner, 'comercial');
        $approved = $this->supplyRequest($owner, 'operaciones', 'aprobada_calidad');

        $this->actingAs($reviewer)
            ->get(route('supplies.approval.index', ['module' => 'calidad']))
            ->assertOk()
            ->assertSee('pur-req-list__folio">'.$pendingOperaciones->folioVisible(), false)
            ->assertSee('pur-req-list__folio">'.$comercialPending->folioVisible(), false)
            ->assertDontSee('pur-req-list__folio">'.$approved->folioVisible(), false)
            ->assertSee('Por autorizar');

        $this->actingAs($reviewer)
            ->get(route('supplies.approval.index', ['module' => 'calidad', 'area_key' => 'comercial']))
            ->assertOk()
            ->assertSee('pur-req-list__folio">'.$comercialPending->folioVisible(), false)
            ->assertDontSee('pur-req-list__folio">'.$pendingOperaciones->folioVisible(), false);
    }

    public function test_quality_reviewer_from_calidad_can_approve_supply_of_another_area(): void
    {
        $owner = $this->requester('operaciones');
        $reviewer = $this->qualityReviewer('calidad');
        $request = $this->supplyRequest($owner, 'operaciones');
        $item = $request->items()->firstOrFail();

        $this->actingAs($reviewer)
            ->get(route('supplies.approval.edit', ['module' => 'calidad', 'supply_request' => $request->id]))
            ->assertOk();

        $this->actingAs($reviewer)->patch(route('supplies.approval.update', [
            'module' => 'calidad',
            'supply_request' => $request->id,
        ]), [
            'action' => 'approve',
            'items' => [$item->id => ['approved_quantity' => 5]],
        ])->assertRedirect(route('supplies.approval.index', ['module' => 'calidad']));

        $this->assertSame('aprobada_calidad', $request->fresh()->status);
    }

    public function test_default_supply_board_url_uses_first_authorized_tab(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo([
            'view.board.calidad.suministros',
            'supply.tab.quality',
        ]);

        $this->assertSame(
            route('supplies.approval.index', ['module' => 'calidad']),
            $user->defaultSupplyBoardUrl('calidad')
        );
    }

    public function test_dashboard_redirects_to_default_supply_board(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('view.board.calidad.suministros');
        $user->givePermissionTo('supply.tab.quality');

        $response = $this->actingAs($user)->get(route('dashboard', [
            'module' => 'calidad',
            'board' => 'suministros',
        ]));

        $response->assertRedirect($user->defaultSupplyBoardUrl('calidad'));
    }

    public function test_store_notifies_approval_reviewers(): void
    {
        Mail::fake();

        $requester = $this->requester('operaciones');
        $reviewer = User::factory()->create([
            'area_key' => 'calidad',
            'email' => 'calidad.reviewer@example.com',
            'must_change_password' => false,
        ]);
        $reviewer->assignRole('usuario');
        $reviewer->givePermissionTo([
            'view.board.calidad.suministros',
            'supply.tab.quality',
        ]);

        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($requester)->post(route('supplies.store', ['module' => 'operaciones']), [
            'observations' => 'Pedido mensual.',
            'items' => [
                [
                    'type' => 'catalog',
                    'product_id' => $product->id,
                    'current_inventory' => 1,
                    'quantity' => 5,
                ],
            ],
        ]);

        Mail::assertQueued(SupplyRequestNotification::class, function (SupplyRequestNotification $mail) use ($reviewer) {
            return $mail->hasTo($reviewer->email);
        });
    }

    public function test_user_without_site_cannot_create_supply_request(): void
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'sede_id' => null,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        $product = SupplyProduct::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('supplies.store', ['module' => 'operaciones']), [
            'items' => [
                [
                    'type' => 'catalog',
                    'product_id' => $product->id,
                    'current_inventory' => 1,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('sede');
    }

    public function test_create_form_warns_when_user_has_no_site(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'sede_id' => null,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        $this->actingAs($user)
            ->get(route('supplies.create', ['module' => 'calidad']))
            ->assertOk()
            ->assertSee('Debes tener una sede asignada y activa', false);
    }

    public function test_failed_store_shows_errors_and_keeps_old_items(): void
    {
        $user = User::factory()->create([
            'area_key' => 'calidad',
            'sede_id' => null,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        $product = SupplyProduct::query()->firstOrFail();

        $response = $this->actingAs($user)
            ->from(route('supplies.create', ['module' => 'calidad']))
            ->post(route('supplies.store', ['module' => 'calidad']), [
                'observations' => 'Pedido de prueba.',
                'items' => [
                    [
                        'type' => 'catalog',
                        'product_id' => $product->id,
                        'current_inventory' => 2,
                        'quantity' => 4,
                    ],
                    [
                        'type' => 'custom',
                        'custom_name' => 'Tinta especial',
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect(route('supplies.create', ['module' => 'calidad']));
        $response->assertSessionHasErrors('sede');

        $this->actingAs($user)
            ->get(route('supplies.create', ['module' => 'calidad']))
            ->assertOk()
            ->assertSee('No se pudo enviar la solicitud', false)
            ->assertSee('Debe tener una sede asignada', false)
            ->assertSee('Tinta especial', false)
            ->assertSee((string) $product->id, false)
            ->assertSee('Pedido de prueba.', false);
    }

    public function test_approved_tab_is_visible_with_quality_permission(): void
    {
        $reviewer = $this->qualityReviewer('calidad');

        $tabs = $reviewer->supplyBoardTabsFor('calidad');

        $this->assertTrue($tabs->contains('aprobacion_insumos'));
        $this->assertFalse($tabs->contains('insumos_aprobados'));
        $this->assertFalse($tabs->contains('catalogo'));
    }

    public function test_approved_index_redirects_to_por_aprobar(): void
    {
        $reviewer = $this->qualityReviewer('calidad');

        $this->actingAs($reviewer)
            ->get(route('supplies.approved.index', ['module' => 'calidad']))
            ->assertRedirect(route('supplies.approval.index', ['module' => 'calidad']));
    }

    public function test_approved_index_filters_by_export_status(): void
    {
        $reviewer = $this->qualityReviewer('calidad');

        $this->actingAs($reviewer)
            ->get(route('supplies.approved.index', [
                'module' => 'calidad',
                'export_status' => 'pending',
            ]))
            ->assertRedirect(route('supplies.approval.index', ['module' => 'calidad']));
    }

    public function test_approved_export_downloads_excel_for_single_request(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $user = $this->requester('operaciones', $site);
        $request = $this->approvedSupplyRequest($user, 'operaciones', $site, $product, 5);

        $reviewer = $this->qualityReviewer('calidad');

        $response = $this->actingAs($reviewer)->get(route('supplies.approved.export', [
            'module' => 'calidad',
            'supply_request' => $request->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertNotNull($request->fresh()->exported_at);
    }

    public function test_approved_export_can_be_downloaded_again_after_export(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $user = $this->requester('operaciones', $site);
        $request = $this->approvedSupplyRequest($user, 'operaciones', $site, $product, 2);
        $request->update(['exported_at' => now()]);

        $reviewer = $this->qualityReviewer('calidad');

        $response = $this->actingAs($reviewer)->get(route('supplies.approved.export', [
            'module' => 'calidad',
            'supply_request' => $request->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_approved_export_merges_quantities_for_duplicate_description_and_reference(): void
    {
        $site = SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();
        $user = $this->requester('operaciones', $site);

        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => 'operaciones',
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'aprobada_calidad',
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 5,
            'approved_quantity' => 5,
            'is_not_in_catalog' => false,
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => 3,
            'approved_quantity' => 3,
            'is_not_in_catalog' => false,
        ]);

        $exporter = app(SupplyPurchaseReportExporter::class);
        $rows = $exporter->buildMergedRowsForRequest($request);

        $this->assertCount(1, $rows);
        $this->assertSame(8, $rows->first()['quantity']);
    }

    private function qualityReviewer(string $areaKey): User
    {
        $user = $this->requester($areaKey);
        $user->givePermissionTo('view.board.'.$areaKey.'.suministros');
        $user->givePermissionTo('supply.tab.quality');

        return $user;
    }

    private function requester(string $areaKey, ?SupplySite $site = null): User
    {
        $site ??= SupplySite::query()->where('name', 'cali_central')->firstOrFail();

        $user = User::factory()->create([
            'area_key' => $areaKey,
            'sede_id' => $site->id,
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('supply.tab.my_requests');

        return $user;
    }

    private function supplyRequest(User $user, string $areaKey, string $status = 'pendiente_calidad'): SupplyRequest
    {
        $site = $user->site ?? SupplySite::query()->where('name', 'cali_central')->firstOrFail();
        $product = SupplyProduct::query()->firstOrFail();

        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $areaKey,
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => $status,
            'observations' => 'Solicitud de prueba.',
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'current_inventory' => 1,
            'requested_quantity' => 8,
            'is_not_in_catalog' => false,
        ]);

        return $request;
    }

    private function approvedSupplyRequest(
        User $user,
        string $areaKey,
        SupplySite $site,
        SupplyProduct $product,
        int $approvedQuantity
    ): SupplyRequest {
        $request = SupplyRequest::create([
            'user_id' => $user->id,
            'area_key' => $areaKey,
            'sede_id' => $site->id,
            'site_utilization' => $site->utilization,
            'site_city' => $site->city,
            'status' => 'aprobada_calidad',
        ]);

        $request->items()->create([
            'supply_product_id' => $product->id,
            'requested_quantity' => $approvedQuantity,
            'approved_quantity' => $approvedQuantity,
            'is_not_in_catalog' => false,
        ]);

        return $request;
    }

    public function test_compras_user_sees_supply_detail_like_purchase_with_export_actions(): void
    {
        $requester = $this->requester('operaciones');
        $supplyRequest = $this->approvedSupplyRequest(
            $requester,
            'operaciones',
            SupplySite::query()->where('name', 'cali_central')->firstOrFail(),
            SupplyProduct::query()->firstOrFail(),
            5,
        );

        $compras = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $compras->givePermissionTo('purchase.tab.processing');

        $this->actingAs($compras)
            ->get(route('supplies.show', ['module' => 'operaciones', 'supply_request' => $supplyRequest->id]))
            ->assertOk()
            ->assertSee('Solicitud de suministro '.$supplyRequest->folioVisible())
            ->assertSee('Volver a Pedidos')
            ->assertSee('Descargar PDF')
            ->assertSee('Exportar Excel');
    }

    public function test_approved_supply_can_export_pdf_from_show(): void
    {
        $requester = $this->requester('operaciones');
        $supplyRequest = $this->approvedSupplyRequest(
            $requester,
            'operaciones',
            SupplySite::query()->where('name', 'cali_central')->firstOrFail(),
            SupplyProduct::query()->firstOrFail(),
            3,
        );

        $this->actingAs($requester)
            ->get(route('supplies.export.pdf', ['module' => 'operaciones', 'supply_request' => $supplyRequest->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $html = view('pdf.supply-request-solicitud', [
            'supplyRequest' => $supplyRequest->load(['user', 'items.product', 'qualityReviewer', 'purchasingManager']),
            'lineItems' => app(SupplyPurchaseReportExporter::class)
                ->buildMergedRowsForRequest($supplyRequest)
                ->values()
                ->map(fn (array $row): array => $row + ['photo_data_uri' => null])
                ->all(),
            'generatedAt' => now(),
            'formCode' => config('supplies.report.form_code'),
            'formVersion' => config('supplies.report.version'),
            'formPeriod' => config('supplies.report.period'),
            'photoPlaceholder' => config('supplies.report.photo_placeholder'),
            'reportTitle' => config('supplies.report.title'),
            'pageLabel' => 'Página 1 de 1',
        ])->render();

        $this->assertStringContainsString('Fecha solicitud', $html);
        $this->assertStringContainsString('Revisor calidad', $html);
        $this->assertStringContainsString('Productos solicitados', $html);
        $this->assertStringContainsString($requester->name, $html);
        $this->assertStringContainsString('Solicitud de suministro', $html);
        $this->assertStringContainsString('Descripción', $html);
        $this->assertStringContainsString('Marzo 2026', $html);
    }
}
