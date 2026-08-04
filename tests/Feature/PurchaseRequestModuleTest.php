<?php

namespace Tests\Feature;

use App\Mail\PurchaseRequestCreatedMail;
use App\Mail\PurchaseRequestResolvedMail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestMailLog;
use App\Models\SupplyProduct;
use App\Models\SupplyRequest;
use App\Models\SupplySite;
use App\Models\User;
use App\Services\Compras\ComprasQueueFilterBag;
use App\Services\Compras\ComprasQueueService;
use App\Services\Navigation\NavigationResolver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseRequestModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_can_store_purchase_request_without_explicit_tab_permissions(): void
    {
        Mail::fake();

        Role::findOrCreate('super-admin', 'web');

        $superAdmin = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $superAdmin->assignRole('super-admin');
        $superAdmin->syncPermissions([]);

        $director = $this->director();

        $this->actingAs($superAdmin)->post(route('purchase-requests.store', ['module' => 'compras']), [
            'area_key' => 'compras',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Prueba super-admin',
                    'referencia' => 'N/A',
                    'utilizacion' => 'Test',
                    'ubicacion' => 'Cali',
                ],
            ],
        ])->assertRedirect(route('purchase-requests.index', ['module' => 'compras']));

        Mail::assertSent(PurchaseRequestCreatedMail::class);
    }

    public function test_user_with_create_permission_can_store_from_any_purchase_module(): void
    {
        Mail::fake();

        $requester = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $requester->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'view.board.gestion_humana.solicitudes_compra',
        ]);

        $director = $this->director();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'compras']), [
            'area_key' => 'gestion_humana',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Silla',
                    'referencia' => 'Ergonómica',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Bogota',
                ],
            ],
        ])->assertRedirect(route('purchase-requests.index', ['module' => 'compras']));
    }

    public function test_user_without_create_permission_cannot_store_purchase_request(): void
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo('view.board.compras.solicitudes_compra');

        $director = $this->director();

        $this->actingAs($user)->post(route('purchase-requests.store', ['module' => 'compras']), [
            'area_key' => 'compras',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Test',
                    'referencia' => 'Ref',
                    'utilizacion' => 'Uso',
                    'ubicacion' => 'Cali',
                ],
            ],
        ])->assertForbidden();
    }

    public function test_user_can_create_purchase_request_from_compras_canonical_module(): void
    {
        Mail::fake();

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $requester->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            'view.board.compras.solicitudes_compra',
        ]);

        $director = $this->director();

        $response = $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'compras']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Teclado',
                    'referencia' => 'Logitech',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Cali',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.index', ['module' => 'compras']));
        Mail::assertSent(PurchaseRequestCreatedMail::class);
    }

    public function test_user_can_create_purchase_request_and_notify_selected_director(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $response = $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 2,
                    'descripcion' => 'Monitor 24 pulgadas',
                    'referencia' => 'Dell P2422H',
                    'utilizacion' => 'Puesto de trabajo',
                    'ubicacion' => 'Cali',
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.index', ['module' => 'operaciones']));
        $this->assertDatabaseHas('purchase_requests', [
            'user_id' => $requester->id,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
            'urgente' => false,
        ]);

        Mail::assertSent(PurchaseRequestCreatedMail::class, fn ($mail) => $mail->hasTo($director->email));
        Mail::assertNotSent(PurchaseRequestCreatedMail::class, fn ($mail) => $mail->hasTo($requester->email));

        $purchaseRequest = PurchaseRequest::query()->first();
        $this->assertNotNull($purchaseRequest);
        $this->assertDatabaseHas('purchase_request_mail_logs', [
            'purchase_request_id' => $purchaseRequest->id,
            'recipient_email' => $director->email,
            'status' => 'enviado',
        ]);
    }

    public function test_user_can_create_purchase_request_marked_as_urgent(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => '1',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Silla ergonomica',
                    'referencia' => 'CH-100',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Bogota',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('purchase_requests', [
            'user_id' => $requester->id,
            'urgente' => true,
        ]);
    }

    public function test_my_requests_lists_own_requests_regardless_of_area_key(): void
    {
        $requester = $this->purchaseRequester('compras');
        $director = $this->director();

        $purchaseRequest = PurchaseRequest::query()->create([
            'numero_solicitud' => 1,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Prueba area distinta',
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $purchaseRequest->items()->create([
            'orden' => 1,
            'cantidad' => 2,
            'descripcion' => 'Producto prueba',
            'referencia' => 'REF-1',
            'utilizacion' => 'Oficina',
            'ubicacion' => 'Cali',
        ]);

        $this->actingAs($requester)
            ->get(route('purchase-requests.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee($purchaseRequest->folio())
            ->assertSee('Director aprobador')
            ->assertSee($director->name)
            ->assertSee($requester->name);
    }

    public function test_show_displays_mail_log_registry(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $purchaseRequest->mailLogs()->create([
            'mail_type' => PurchaseRequestMailLog::TYPE_DIRECTOR_ASSIGNED,
            'recipient_email' => $director->email,
            'status' => PurchaseRequestMailLog::STATUS_ENVIADO,
            'sent_at' => now(),
        ]);

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', ['module' => 'operaciones', 'purchase_request' => $purchaseRequest->id]))
            ->assertOk()
            ->assertSee('Registro de correos de esta solicitud')
            ->assertSee($director->email)
            ->assertSee('Enviado');
    }

    public function test_pdf_export_includes_detail_metadata(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = PurchaseRequest::query()->create([
            'numero_solicitud' => 99,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Equipo de oficina',
            'cantidad' => 2,
            'justificacion' => 'Reposicion anual',
            'solicitud_para' => 'Interno',
            'urgente' => true,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_PENDIENTE,
        ]);

        $purchaseRequest->items()->create([
            'orden' => 1,
            'cantidad' => 2,
            'descripcion' => 'Monitor LED',
            'referencia' => 'MON-001',
            'utilizacion' => 'Puesto administrativo',
            'ubicacion' => 'Cali',
        ]);

        $response = $this->actingAs($requester)->get(route('purchase-requests.export.pdf', [
            'module' => 'operaciones',
            'purchase_request' => $purchaseRequest->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $html = view('pdf.purchase-request-solicitud', [
            'purchaseRequest' => $purchaseRequest->load(['user', 'aprobador', 'items', 'procesadoComprasPor']),
            'itemPhotos' => [],
            'generatedAt' => now(),
            'formCode' => config('purchase-requests.form_code'),
            'formVersion' => config('purchase-requests.form_version'),
            'reportTitle' => config('purchase-requests.report_title'),
        ])->render();

        $this->assertStringContainsString('Fecha solicitud', $html);
        $this->assertStringContainsString('Director aprobador', $html);
        $this->assertStringContainsString('Productos solicitados', $html);
        $this->assertTrue(
            str_contains($html, $director->name)
            || str_contains($html, htmlspecialchars($director->name, ENT_QUOTES, 'UTF-8')),
            'Expected director name in PDF output.',
        );
        $this->assertStringContainsString($requester->name, $html);
        $this->assertStringContainsString('Monitor LED', $html);
        $this->assertStringContainsString('Urgente', $html);
        $this->assertStringNotContainsString('Registro de correos', $html);
    }

    public function test_store_persists_descripcion_and_justificacion_from_form(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Equipo para area TIC',
            'justificacion' => 'Renovacion de hardware',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Monitor',
                    'referencia' => 'Dell',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Cali',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('purchase_requests', [
            'user_id' => $requester->id,
            'descripcion' => 'Equipo para area TIC',
            'justificacion' => 'Renovacion de hardware',
        ]);
    }

    public function test_show_displays_uploaded_item_photo(): void
    {
        Storage::fake('public');

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud con foto',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Teclado mecanico',
                    'referencia' => 'KB-001',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Bogota',
                    'foto' => UploadedFile::fake()->image('teclado.jpg'),
                ],
            ],
        ]);

        $purchaseRequest = PurchaseRequest::query()->first();
        $item = $purchaseRequest?->items()->first();
        $this->assertNotNull($item?->fotoUrl());

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', [
                'module' => 'operaciones',
                'purchase_request' => $purchaseRequest->id,
            ]))
            ->assertOk()
            ->assertSee($item->fotoUrl(), false)
            ->assertSee('Descripcion general', false)
            ->assertSee('Solicitud con foto', false);
    }

    public function test_user_can_upload_item_photo_when_creating_purchase_request(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $response = $this->actingAs($requester)->post(route('purchase-requests.store', ['module' => 'operaciones']), [
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud de prueba super-admin',
            'items' => [
                [
                    'cantidad' => 1,
                    'descripcion' => 'Teclado mecanico',
                    'referencia' => 'KB-001',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Bogota',
                    'foto' => UploadedFile::fake()->image('teclado.jpg'),
                ],
            ],
        ]);

        $response->assertRedirect(route('purchase-requests.index', ['module' => 'operaciones']));

        $item = PurchaseRequest::query()->first()?->items()->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->foto_path);
        $this->assertTrue(Storage::disk('public')->exists($item->foto_path));
    }

    public function test_director_sees_only_assigned_pending_requests(): void
    {
        $directorA = $this->director('director.a@test.local');
        $directorB = $this->director('director.b@test.local');
        $requester = $this->purchaseRequester('operaciones');

        $assigned = $this->createPurchaseRequest($requester, $directorA);
        $this->createPurchaseRequest($requester, $directorB);

        $response = $this->actingAs($directorA)->get(route('purchase-requests.approval.index', ['module' => 'compras']));

        $response->assertOk();
        $response->assertSee($assigned->folio());
        $response->assertDontSee(PurchaseRequest::query()->where('aprobador_id', $directorB->id)->first()->folio());
    }

    public function test_director_approval_index_keeps_resolved_history_with_filter(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');

        $pending = $this->createPurchaseRequest($requester, $director);
        $approved = $this->createPurchaseRequest($requester, $director, PurchaseRequest::ESTADO_APROBADO);

        $this->actingAs($director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras']))
            ->assertOk()
            ->assertSee($pending->folio())
            ->assertDontSee($approved->folio());

        $this->actingAs($director)
            ->get(route('purchase-requests.approval.index', ['module' => 'compras', 'estado' => 'todos']))
            ->assertOk()
            ->assertSee($pending->folio())
            ->assertSee($approved->folio());
    }

    public function test_non_assigned_director_cannot_approve(): void
    {
        $directorA = $this->director('director.a@test.local');
        $directorB = $this->director('director.b@test.local');
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $directorA);

        $this->actingAs($directorB)
            ->patch(route('purchase-requests.approval.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
                'estado' => PurchaseRequest::ESTADO_APROBADO,
            ])
            ->assertForbidden();
    }

    public function test_approved_purchase_appears_in_compras_queue(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $compras = $this->comprasUser();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
        ])->assertRedirect();

        $response = $this->actingAs($compras)->get(route('purchase-requests.processing.index', ['module' => 'compras']));

        $response->assertOk();
        $response->assertSee($purchaseRequest->fresh()->folio());
        $response->assertSee('Ver detalle', false);
        $response->assertSee('Filtros', false);
        $response->assertSee('mostrad', false);
    }

    public function test_completed_purchase_remains_in_bandeja_queue(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');

        PurchaseRequest::query()->create([
            'numero_solicitud' => 8801,
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Completada bandeja',
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_COMPLETADO,
            'fecha_aprobacion' => now()->toDateString(),
            'procesado_compras_at' => now(),
        ]);

        $result = app(ComprasQueueService::class)->resolve(new ComprasQueueFilterBag(
            estadoCompras: '',
            tipo: null,
            areaKey: null,
            dateFrom: null,
            dateTo: null,
        ));

        $this->assertTrue($result['items']->contains(fn (array $item): bool => $item['folio'] === '8801'));
        $this->assertSame('Completado', $result['items']->firstWhere('folio', '8801')['estado_label']);
    }

    public function test_completed_supply_remains_in_bandeja_queue_with_completado_label(): void
    {
        $requester = $this->supplyRequester('operaciones');

        $supplyRequest = SupplyRequest::query()->create([
            'user_id' => $requester->id,
            'area_key' => 'operaciones',
            'sede_id' => $requester->sede_id,
            'status' => 'completada',
            'updated_at' => now(),
        ]);

        $result = app(ComprasQueueService::class)->resolve(new ComprasQueueFilterBag(
            estadoCompras: '',
            tipo: null,
            areaKey: null,
            dateFrom: null,
            dateTo: null,
        ));

        $item = $result['items']->firstWhere('tipo', 'supply');

        $this->assertNotNull($item);
        $this->assertSame($supplyRequest->folio(), $item['folio']);
        $this->assertSame(PurchaseRequest::COMPRAS_COMPLETADO, $item['estado']);
        $this->assertSame('Completado', $item['estado_label']);
    }

    public function test_bandeja_queue_truncates_to_two_hundred_without_date_filter(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');

        for ($i = 1; $i <= 205; $i++) {
            PurchaseRequest::query()->create([
                'numero_solicitud' => 7000 + $i,
                'user_id' => $requester->id,
                'area_key' => 'operaciones',
                'fecha_solicitud' => now()->toDateString(),
                'descripcion' => "Lote {$i}",
                'cantidad' => 1,
                'justificacion' => 'Prueba',
                'solicitud_para' => 'Interno',
                'urgente' => false,
                'aprobador_id' => $director->id,
                'estado' => PurchaseRequest::ESTADO_APROBADO,
                'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
                'fecha_aprobacion' => now()->subDays(205 - $i)->toDateString(),
            ]);
        }

        $result = app(ComprasQueueService::class)->resolve(new ComprasQueueFilterBag(
            estadoCompras: '',
            tipo: null,
            areaKey: null,
            dateFrom: null,
            dateTo: null,
        ));

        $this->assertTrue($result['truncated']);
        $this->assertSame(205, $result['total_matching']);
        $this->assertCount(ComprasQueueService::DEFAULT_LIMIT, $result['items']);
    }

    public function test_bandeja_queue_returns_all_in_date_range_even_over_two_hundred(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $dateFrom = now()->subDays(10)->toDateString();
        $dateTo = now()->toDateString();

        for ($i = 1; $i <= 210; $i++) {
            PurchaseRequest::query()->create([
                'numero_solicitud' => 6000 + $i,
                'user_id' => $requester->id,
                'area_key' => 'operaciones',
                'fecha_solicitud' => now()->toDateString(),
                'descripcion' => "Rango {$i}",
                'cantidad' => 1,
                'justificacion' => 'Prueba',
                'solicitud_para' => 'Interno',
                'urgente' => false,
                'aprobador_id' => $director->id,
                'estado' => PurchaseRequest::ESTADO_APROBADO,
                'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
                'fecha_aprobacion' => now()->subDays($i % 11)->toDateString(),
            ]);
        }

        $result = app(ComprasQueueService::class)->resolve(new ComprasQueueFilterBag(
            estadoCompras: '',
            tipo: null,
            areaKey: null,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        ));

        $this->assertFalse($result['truncated']);
        $this->assertSame(210, $result['total_matching']);
        $this->assertCount(210, $result['items']);
    }

    public function test_compras_queue_service_filters_by_area(): void
    {
        $director = $this->director();
        $operacionesRequester = $this->purchaseRequester('operaciones');
        $comercialRequester = $this->purchaseRequester('comercial');

        PurchaseRequest::query()->create([
            'numero_solicitud' => 9101,
            'user_id' => $operacionesRequester->id,
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Operaciones bandeja',
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
            'fecha_aprobacion' => now()->toDateString(),
        ]);

        PurchaseRequest::query()->create([
            'numero_solicitud' => 9202,
            'user_id' => $comercialRequester->id,
            'area_key' => 'comercial',
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Comercial bandeja',
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'estado_compras' => PurchaseRequest::COMPRAS_PENDIENTE,
            'fecha_aprobacion' => now()->toDateString(),
        ]);

        $items = app(ComprasQueueService::class)->items(
            new ComprasQueueFilterBag(
                estadoCompras: '',
                tipo: null,
                areaKey: 'operaciones',
                dateFrom: null,
                dateTo: null,
            )
        );

        $this->assertCount(1, $items);
        $this->assertSame('9101', $items->first()['folio']);
    }

    public function test_compras_analyst_can_open_purchase_detail_from_bandeja(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $compras = $this->comprasUser();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
        ])->assertRedirect();

        $this->actingAs($compras)
            ->get(route('purchase-requests.show', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]))
            ->assertOk()
            ->assertSee($purchaseRequest->folio())
            ->assertSee('Descargar PDF')
            ->assertSee('Registro de correos de esta solicitud');
    }

    public function test_director_can_approve_from_show_page(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)
            ->get(route('purchase-requests.show', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]))
            ->assertOk()
            ->assertSee('Aprobar solicitud');

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
        ])->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras']));

        $this->assertSame(PurchaseRequest::ESTADO_APROBADO, $purchaseRequest->fresh()->estado);
    }

    public function test_director_approval_notifies_requester_with_decision_and_comments(): void
    {
        Mail::fake();

        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', [
            'module' => 'compras',
            'purchase_request' => $purchaseRequest->id,
        ]), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'comentarios_director' => 'Autorizado. Proceder con cotizacion.',
        ])->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras']));

        Mail::assertSent(PurchaseRequestResolvedMail::class, function (PurchaseRequestResolvedMail $mail) use ($requester, $purchaseRequest): bool {
            return $mail->hasTo($requester->email)
                && $mail->purchaseRequest->is($purchaseRequest->fresh())
                && $mail->purchaseRequest->comentarios_director === 'Autorizado. Proceder con cotizacion.';
        });
    }

    public function test_director_rejection_notifies_requester_with_observations(): void
    {
        Mail::fake();

        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($director)->patch(route('purchase-requests.approval.update', [
            'module' => 'compras',
            'purchase_request' => $purchaseRequest->id,
        ]), [
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
            'comentarios_director' => 'Presupuesto no disponible este trimestre.',
        ])->assertRedirect(route('purchase-requests.approval.index', ['module' => 'compras']));

        $this->assertSame(PurchaseRequest::ESTADO_RECHAZADO, $purchaseRequest->fresh()->estado);

        Mail::assertSent(PurchaseRequestResolvedMail::class, function (PurchaseRequestResolvedMail $mail) use ($requester): bool {
            return $mail->hasTo($requester->email)
                && $mail->purchaseRequest->estado === PurchaseRequest::ESTADO_RECHAZADO
                && $mail->purchaseRequest->comentarios_director === 'Presupuesto no disponible este trimestre.';
        });
    }

    public function test_requester_can_edit_rejected_purchase_request(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director, PurchaseRequest::ESTADO_RECHAZADO, 'Ajustar cantidades');

        $this->actingAs($requester)
            ->get(route('purchase-requests.edit', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]))
            ->assertOk()
            ->assertSee('Reabrir solicitud')
            ->assertSee('Motivo del rechazo')
            ->assertSee('Ajustar cantidades');
    }

    public function test_requester_cannot_edit_non_rejected_purchase_request(): void
    {
        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->actingAs($requester)
            ->get(route('purchase-requests.edit', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]))
            ->assertForbidden();
    }

    public function test_resubmit_rejected_request_resets_pending_and_notifies_director(): void
    {
        Mail::fake();

        $director = $this->director();
        $requester = $this->purchaseRequester('operaciones');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director, PurchaseRequest::ESTADO_RECHAZADO, 'Faltan referencias');

        $this->actingAs($requester)->patch(
            route('purchase-requests.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]),
            $this->resubmitPayload($director, [
                'descripcion' => 'Solicitud corregida tras rechazo',
                'items' => [[
                    'cantidad' => 3,
                    'descripcion' => 'Teclado mecanico',
                    'referencia' => 'KB-002',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Cali',
                ]],
            ])
        )->assertRedirect(route('purchase-requests.index', ['module' => 'compras']));

        $fresh = $purchaseRequest->fresh();
        $this->assertSame(PurchaseRequest::ESTADO_PENDIENTE, $fresh->estado);
        $this->assertNull($fresh->comentarios_director);
        $this->assertNull($fresh->fecha_aprobacion);
        $this->assertSame('Solicitud corregida tras rechazo', $fresh->descripcion);
        $this->assertCount(1, $fresh->items);
        $this->assertSame(3, $fresh->items->first()->cantidad);

        Mail::assertSent(PurchaseRequestCreatedMail::class, fn ($mail) => $mail->hasTo($director->email));
    }

    public function test_email_signed_link_shows_guest_approval_form(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->get($this->emailApprovalShowUrl($purchaseRequest, $director))
            ->assertOk()
            ->assertSee('Autorizacion de solicitud de compra', false)
            ->assertSee('Aprobar solicitud')
            ->assertSee('Ver PDF (FO-AD-44)');
    }

    public function test_email_approval_post_approves_request_without_login(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->post($this->emailApprovalUpdateUrl($purchaseRequest, $director), [
            'estado' => PurchaseRequest::ESTADO_APROBADO,
            'comentarios_director' => 'Aprobado por correo',
        ])->assertOk()
            ->assertSee('Decision registrada')
            ->assertSee('aprobada');

        $this->assertSame(PurchaseRequest::ESTADO_APROBADO, $purchaseRequest->fresh()->estado);
        Mail::assertSent(PurchaseRequestResolvedMail::class, function (PurchaseRequestResolvedMail $mail) use ($requester): bool {
            return $mail->hasTo($requester->email)
                && $mail->purchaseRequest->comentarios_director === 'Aprobado por correo';
        });
    }

    public function test_email_approval_post_rejection_notifies_requester(): void
    {
        Mail::fake();

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->post($this->emailApprovalUpdateUrl($purchaseRequest, $director), [
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
            'comentarios_director' => 'No cumple politica de compras.',
        ])->assertOk();

        Mail::assertSent(PurchaseRequestResolvedMail::class, function (PurchaseRequestResolvedMail $mail) use ($requester): bool {
            return $mail->hasTo($requester->email)
                && $mail->purchaseRequest->estado === PurchaseRequest::ESTADO_RECHAZADO
                && $mail->purchaseRequest->comentarios_director === 'No cumple politica de compras.';
        });
    }

    public function test_email_approval_post_rejects_requires_comment(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->post($this->emailApprovalUpdateUrl($purchaseRequest, $director), [
            'estado' => PurchaseRequest::ESTADO_RECHAZADO,
        ])->assertSessionHasErrors('comentarios_director');

        $this->assertSame(PurchaseRequest::ESTADO_PENDIENTE, $purchaseRequest->fresh()->estado);
    }

    public function test_email_approval_invalid_signature_is_forbidden(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->get($this->emailApprovalShowUrl($purchaseRequest, $director).'&invalid=1')
            ->assertForbidden();
    }

    public function test_email_approval_wrong_director_is_forbidden(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $otherDirector = $this->director('otro.director@test.local');
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);

        $this->get($this->emailApprovalShowUrl($purchaseRequest, $otherDirector))
            ->assertForbidden();
    }

    public function test_email_approval_already_resolved_shows_message(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->createPurchaseRequest($requester, $director);
        $purchaseRequest->update(['estado' => PurchaseRequest::ESTADO_APROBADO]);

        $this->get($this->emailApprovalShowUrl($purchaseRequest, $director))
            ->assertOk()
            ->assertSee('ya fue gestionada', false)
            ->assertDontSee('Aprobar solicitud');
    }

    public function test_supply_approval_appears_in_compras_queue(): void
    {
        Mail::fake();

        $qualityUser = $this->qualityReviewer('operaciones');
        $compras = $this->comprasUser();
        $requester = $this->supplyRequester('operaciones');
        $product = SupplyProduct::query()->firstOrFail();

        $this->actingAs($requester)->post(route('supplies.store', ['module' => 'operaciones']), [
            'items' => [[
                'type' => 'catalog',
                'product_id' => $product->id,
                'current_inventory' => 1,
                'quantity' => 5,
            ]],
        ]);

        $supplyRequest = SupplyRequest::query()->latest()->firstOrFail();
        $item = $supplyRequest->items()->firstOrFail();

        $this->actingAs($qualityUser)->patch(route('supplies.approval.update', ['module' => 'operaciones', 'supply_request' => $supplyRequest->id]), [
            'action' => 'approve',
            'items' => [$item->id => ['approved_quantity' => 4]],
        ])->assertRedirect();

        $response = $this->actingAs($compras)->get(route('purchase-requests.processing.index', ['module' => 'compras']));

        $response->assertOk();
        $response->assertSee($supplyRequest->folio());
    }

    public function test_navigation_processing_bandeja_activates_solicitudes_compra_board(): void
    {
        $user = $this->comprasUser();

        $nav = app(NavigationResolver::class)->resolve(
            $user,
            'purchase-requests.processing.index',
            'compras',
        );

        $activeAreaKeys = collect($nav['appNavigation'])
            ->filter(fn (array $module): bool => $module['active'])
            ->pluck('key')
            ->values()
            ->all();

        $this->assertSame(['compras'], $activeAreaKeys);

        $comprasBoards = collect(collect($nav['appNavigation'])->firstWhere('key', 'compras')['items'] ?? []);
        $activeBoardLabels = $comprasBoards
            ->filter(fn (array $board): bool => $board['active'])
            ->pluck('label')
            ->values()
            ->all();

        $this->assertSame(['Solicitudes de compra'], $activeBoardLabels);
        $this->assertFalse(
            $comprasBoards->pluck('label')->contains('Bandeja compras')
        );

        $gestionHumana = collect($nav['appNavigation'])->firstWhere('key', 'gestion_humana');
        if ($gestionHumana !== null) {
            $this->assertFalse(
                collect($gestionHumana['items'] ?? [])->pluck('label')->contains('Bandeja compras')
            );
        }
    }

    private function purchaseRequester(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['purchase.tab.create', 'purchase.tab.my_requests', "view.board.{$area}.solicitudes_compra"]);

        return $user;
    }

    private function director(string $email = 'director@test.local'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'must_change_password' => false,
        ]);
        $user->assignRole('director');

        return $user;
    }

    private function comprasUser(): User
    {
        $user = User::factory()->create([
            'area_key' => 'compras',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['purchase.tab.processing', 'view.board.compras.bandeja_compras']);

        return $user;
    }

    private function qualityReviewer(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['supply.tab.quality', "view.board.{$area}.suministros"]);

        return $user;
    }

    private function supplyRequester(string $area): User
    {
        $user = User::factory()->create([
            'area_key' => $area,
            'must_change_password' => false,
            'sede_id' => SupplySite::query()->value('id'),
        ]);
        $user->givePermissionTo(['supply.tab.my_requests', "view.board.{$area}.suministros"]);

        return $user;
    }

    private function createPurchaseRequest(
        User $requester,
        User $director,
        string $estado = PurchaseRequest::ESTADO_PENDIENTE,
        ?string $comentariosDirector = null,
    ): PurchaseRequest {
        return PurchaseRequest::query()->create([
            'numero_solicitud' => (PurchaseRequest::query()->max('numero_solicitud') ?? 0) + 1,
            'user_id' => $requester->id,
            'area_key' => $requester->area_key,
            'fecha_solicitud' => now()->toDateString(),
            'descripcion' => 'Prueba',
            'cantidad' => 1,
            'justificacion' => 'Prueba',
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'estado' => $estado,
            'fecha_aprobacion' => $estado === PurchaseRequest::ESTADO_PENDIENTE ? null : now()->toDateString(),
            'comentarios_director' => $comentariosDirector,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resubmitPayload(User $director, array $overrides = []): array
    {
        return array_merge([
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'descripcion' => 'Solicitud reabierta',
            'justificacion' => 'Correccion tras rechazo',
            'items' => [[
                'cantidad' => 1,
                'descripcion' => 'Monitor',
                'referencia' => 'Dell',
                'utilizacion' => 'Oficina',
                'ubicacion' => 'Bogota',
            ]],
        ], $overrides);
    }

    private function emailApprovalShowUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return URL::temporarySignedRoute(
            'purchase-requests.email-approval.show',
            now()->addHour(),
            ['purchase_request' => $purchaseRequest->id, 'director' => $director->id],
        );
    }

    private function emailApprovalUpdateUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return URL::temporarySignedRoute(
            'purchase-requests.email-approval.update',
            now()->addHour(),
            ['purchase_request' => $purchaseRequest->id, 'director' => $director->id],
        );
    }
}
