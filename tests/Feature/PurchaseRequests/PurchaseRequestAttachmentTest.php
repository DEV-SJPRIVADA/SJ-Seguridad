<?php

namespace Tests\Feature\PurchaseRequests;

use App\Mail\PurchaseRequestCreatedMail;
use App\Models\AuditLog;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAttachment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PurchaseRequestAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        Mail::fake();
        Config::set('audit.enabled', true);
        Config::set('audit.queue', false);
    }

    public function test_store_without_attachments_creates_request(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director))
            ->assertRedirect();

        $purchaseRequest = PurchaseRequest::query()->firstOrFail();

        $this->assertSame(0, $purchaseRequest->attachments()->count());
        $this->assertNull($purchaseRequest->fresh()->archivo_pedido_path);
    }

    public function test_store_persists_one_and_multiple_allowed_attachments_on_local_disk(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => [
                    UploadedFile::fake()->create('cotizacion.pdf', 120, 'application/pdf'),
                ],
            ]))
            ->assertRedirect();

        $first = PurchaseRequest::query()->firstOrFail();
        $this->assertSame(1, $first->attachments()->count());
        $firstAttachment = $first->attachments()->first();
        Storage::disk('local')->assertExists($firstAttachment->stored_path);
        $this->assertSame('cotizacion.pdf', $firstAttachment->original_name);
        $this->assertStringStartsWith('purchase-requests/'.$first->id.'/', $firstAttachment->stored_path);

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', [
                'module' => 'operaciones',
                'purchase_request' => $first->id,
            ]))
            ->assertOk()
            ->assertSee('cotizacion.pdf', false)
            ->assertSee('Adjuntos', false)
            ->assertDontSee('/storage/purchase-requests', false);

        $requesterB = $this->purchaseRequester('operaciones', 'requester.b@test.local');

        $this->actingAs($requesterB)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => [
                    UploadedFile::fake()->create('orden.docx', 80, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                    UploadedFile::fake()->image('evidencia.jpg'),
                    UploadedFile::fake()->create('cuadro.xlsx', 90, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                ],
            ]))
            ->assertRedirect();

        $second = PurchaseRequest::query()->where('user_id', $requesterB->id)->firstOrFail();
        $this->assertSame(3, $second->attachments()->count());

        foreach ($second->attachments as $attachment) {
            Storage::disk('local')->assertExists($attachment->stored_path);
        }

        $this->actingAs($requesterB)
            ->get(route('purchase-requests.show', [
                'module' => 'operaciones',
                'purchase_request' => $second->id,
            ]))
            ->assertOk()
            ->assertSee('orden.docx', false)
            ->assertSee('evidencia.jpg', false)
            ->assertSee('cuadro.xlsx', false);
    }

    public function test_store_rejects_more_than_five_attachments(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $files = [];
        for ($i = 1; $i <= 6; $i++) {
            $files[] = UploadedFile::fake()->create("doc{$i}.pdf", 20, 'application/pdf');
        }

        $this->actingAs($requester)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => $files,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments');

        $this->assertSame(0, PurchaseRequest::query()->count());
        $this->assertSame(0, PurchaseRequestAttachment::query()->count());
    }

    public function test_store_rejects_invalid_type_or_size(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => [
                    UploadedFile::fake()->create('malware.exe', 20, 'application/octet-stream'),
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments.0');

        $this->actingAs($requester)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => [
                    UploadedFile::fake()->create('pesado.pdf', 10241, 'application/pdf'),
                ],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachments.0');

        $this->assertSame(0, PurchaseRequest::query()->count());
        $this->assertSame(0, PurchaseRequestAttachment::query()->count());
    }

    public function test_owner_director_and_processing_can_download_attachment(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $compras = $this->comprasUser();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('cotizacion-original.pdf', 40, 'application/pdf'),
        ]);
        $attachment = $purchaseRequest->attachments()->firstOrFail();

        $this->actingAs($requester)
            ->get($this->downloadUrl($purchaseRequest, $attachment, 'operaciones'))
            ->assertOk()
            ->assertDownload('cotizacion-original.pdf');

        $this->actingAs($director)
            ->get($this->downloadUrl($purchaseRequest, $attachment, 'compras'))
            ->assertOk()
            ->assertDownload('cotizacion-original.pdf');

        $this->actingAs($compras)
            ->get($this->downloadUrl($purchaseRequest, $attachment, 'compras'))
            ->assertOk()
            ->assertDownload('cotizacion-original.pdf');
    }

    public function test_user_without_view_cannot_download_attachment(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $outsider = $this->outsider();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('privado.pdf', 20, 'application/pdf'),
        ]);
        $attachment = $purchaseRequest->attachments()->firstOrFail();

        $this->actingAs($outsider)
            ->get($this->downloadUrl($purchaseRequest, $attachment))
            ->assertForbidden();
    }

    public function test_download_attachment_from_another_request_returns_not_found(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $own = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('mio.pdf', 20, 'application/pdf'),
        ]);
        $otherRequester = $this->purchaseRequester('operaciones', 'otro@test.local');
        $other = $this->storeRequestWithAttachments($otherRequester, $director, [
            UploadedFile::fake()->create('ajeno.pdf', 20, 'application/pdf'),
        ]);

        $this->actingAs($requester)
            ->get(route('purchase-requests.attachments.download', [
                'module' => 'operaciones',
                'purchase_request' => $own->id,
                'attachment' => $other->attachments()->firstOrFail()->id,
            ]))
            ->assertNotFound();
    }

    public function test_guest_cannot_download_attachment(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('guest.pdf', 20, 'application/pdf'),
        ]);
        $attachment = $purchaseRequest->attachments()->firstOrFail();

        auth()->logout();

        $this->get($this->downloadUrl($purchaseRequest, $attachment))
            ->assertRedirect(route('login'));
    }

    public function test_resubmit_keeps_all_attachment_ids_and_paths(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('keep-a.pdf', 20, 'application/pdf'),
            UploadedFile::fake()->create('keep-b.pdf', 20, 'application/pdf'),
        ]);
        $purchaseRequest->update(['estado' => PurchaseRequest::ESTADO_RECHAZADO]);

        $kept = $purchaseRequest->attachments()->orderBy('id')->get();
        $paths = $kept->pluck('stored_path', 'id')->all();

        $this->actingAs($requester)
            ->patch(
                route('purchase-requests.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]),
                $this->storePayload($director, [
                    'keep_attachment_ids' => $kept->pluck('id')->all(),
                ]),
            )
            ->assertRedirect();

        $purchaseRequest->refresh();
        $this->assertSame(PurchaseRequest::ESTADO_PENDIENTE, $purchaseRequest->estado);
        $this->assertEqualsCanonicalizing(array_keys($paths), $purchaseRequest->attachments()->pluck('id')->all());

        foreach ($purchaseRequest->attachments as $attachment) {
            $this->assertSame($paths[$attachment->id], $attachment->stored_path);
            Storage::disk('local')->assertExists($attachment->stored_path);
        }
    }

    public function test_resubmit_can_remove_one_and_add_one(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('permanece.pdf', 20, 'application/pdf'),
            UploadedFile::fake()->create('se-quita.pdf', 20, 'application/pdf'),
        ]);
        $purchaseRequest->update(['estado' => PurchaseRequest::ESTADO_RECHAZADO]);

        $kept = $purchaseRequest->attachments()->where('original_name', 'permanece.pdf')->firstOrFail();
        $removed = $purchaseRequest->attachments()->where('original_name', 'se-quita.pdf')->firstOrFail();
        $removedPath = $removed->stored_path;

        $this->actingAs($requester)
            ->patch(
                route('purchase-requests.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]),
                $this->storePayload($director, [
                    'keep_attachment_ids' => [$kept->id],
                    'attachments' => [
                        UploadedFile::fake()->create('nuevo.pdf', 20, 'application/pdf'),
                    ],
                ]),
            )
            ->assertRedirect();

        $purchaseRequest->refresh();
        $this->assertFalse(PurchaseRequestAttachment::query()->whereKey($removed->id)->exists());
        Storage::disk('local')->assertMissing($removedPath);
        $this->assertTrue($purchaseRequest->attachments()->where('original_name', 'permanece.pdf')->exists());
        $nuevo = $purchaseRequest->attachments()->where('original_name', 'nuevo.pdf')->firstOrFail();
        Storage::disk('local')->assertExists($nuevo->stored_path);
        $this->assertSame(2, $purchaseRequest->attachments()->count());
    }

    public function test_resubmit_can_remove_all_attachments(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('borrar-a.pdf', 20, 'application/pdf'),
            UploadedFile::fake()->create('borrar-b.pdf', 20, 'application/pdf'),
        ]);
        $purchaseRequest->update(['estado' => PurchaseRequest::ESTADO_RECHAZADO]);
        $paths = $purchaseRequest->attachments()->pluck('stored_path');

        $this->actingAs($requester)
            ->patch(
                route('purchase-requests.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]),
                $this->storePayload($director),
            )
            ->assertRedirect();

        $this->assertSame(0, $purchaseRequest->attachments()->count());

        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }
    }

    public function test_created_mail_attaches_only_fo_ad_44_pdf(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => [
                    UploadedFile::fake()->create('no-va-en-correo.pdf', 20, 'application/pdf'),
                    UploadedFile::fake()->image('tampoco.jpg'),
                ],
            ]))
            ->assertRedirect();

        Mail::assertSent(PurchaseRequestCreatedMail::class, function (PurchaseRequestCreatedMail $mail): bool {
            $attachments = $mail->attachments();

            if (count($attachments) !== 1) {
                return false;
            }

            return $attachments[0]->mime === 'application/pdf'
                && ! str_contains((string) $attachments[0]->as, 'no-va-en-correo')
                && ! str_contains((string) $attachments[0]->as, 'tampoco');
        });
    }

    public function test_guest_email_approval_does_not_list_order_attachments(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();
        $purchaseRequest = $this->storeRequestWithAttachments($requester, $director, [
            UploadedFile::fake()->create('cotizacion-secreta-feat030.pdf', 20, 'application/pdf'),
        ]);

        $this->get($this->emailApprovalShowUrl($purchaseRequest, $director))
            ->assertOk()
            ->assertDontSee('cotizacion-secreta-feat030.pdf', false)
            ->assertDontSee('Adjuntos', false);
    }

    public function test_item_photo_still_stores_on_public_disk(): void
    {
        Storage::fake('public');

        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'items' => [[
                    'cantidad' => 1,
                    'descripcion' => 'Teclado mecanico',
                    'referencia' => 'KB-001',
                    'utilizacion' => 'Oficina',
                    'ubicacion' => 'Bogota',
                    'foto' => UploadedFile::fake()->image('teclado.jpg'),
                ]],
                'attachments' => [
                    UploadedFile::fake()->create('pedido.pdf', 20, 'application/pdf'),
                ],
            ]))
            ->assertRedirect();

        $purchaseRequest = PurchaseRequest::query()->firstOrFail();
        $item = $purchaseRequest->items()->firstOrFail();
        $attachment = $purchaseRequest->attachments()->firstOrFail();

        $this->assertNotNull($item->foto_path);
        Storage::disk('public')->assertExists($item->foto_path);
        Storage::disk('local')->assertExists($attachment->stored_path);
        Storage::disk('public')->assertMissing($attachment->stored_path);
    }

    public function test_audit_create_and_resubmit_include_attachments_count_without_paths(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => [
                    UploadedFile::fake()->create('auditoria.pdf', 20, 'application/pdf'),
                ],
            ]))
            ->assertRedirect();

        $purchaseRequest = PurchaseRequest::query()->firstOrFail();
        $attachment = $purchaseRequest->attachments()->firstOrFail();

        $createLog = AuditLog::query()
            ->where('event_type', 'purchase_request')
            ->where('action', 'create')
            ->firstOrFail();

        $this->assertIsInt($createLog->metadata['attachments_count']);
        $this->assertSame(1, $createLog->metadata['attachments_count']);
        $this->assertAuditDoesNotLeakAttachment($createLog, $attachment);

        $purchaseRequest->update(['estado' => PurchaseRequest::ESTADO_RECHAZADO]);

        $this->actingAs($requester)
            ->patch(
                route('purchase-requests.update', ['module' => 'compras', 'purchase_request' => $purchaseRequest->id]),
                $this->storePayload($director, [
                    'keep_attachment_ids' => [$attachment->id],
                    'attachments' => [
                        UploadedFile::fake()->create('auditoria-2.pdf', 20, 'application/pdf'),
                    ],
                ]),
            )
            ->assertRedirect();

        $resubmitLog = AuditLog::query()
            ->where('event_type', 'purchase_request')
            ->where('action', 'resubmit')
            ->firstOrFail();

        $this->assertIsInt($resubmitLog->metadata['attachments_count']);
        $this->assertSame(2, $resubmitLog->metadata['attachments_count']);
        $this->assertAuditDoesNotLeakAttachment($resubmitLog, $attachment);
        $this->assertStringNotContainsString('auditoria-2.pdf', json_encode($resubmitLog->toArray()));
    }

    public function test_show_without_attachments_does_not_render_block(): void
    {
        $requester = $this->purchaseRequester('operaciones');
        $director = $this->director();

        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director))
            ->assertRedirect();

        $purchaseRequest = PurchaseRequest::query()->firstOrFail();

        $this->actingAs($requester)
            ->get(route('purchase-requests.show', [
                'module' => 'operaciones',
                'purchase_request' => $purchaseRequest->id,
            ]))
            ->assertOk()
            ->assertDontSee('Adjuntos', false);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function storeRequestWithAttachments(User $requester, User $director, array $files): PurchaseRequest
    {
        $this->actingAs($requester)
            ->post(route('purchase-requests.store', ['module' => 'operaciones']), $this->storePayload($director, [
                'attachments' => $files,
            ]))
            ->assertRedirect();

        return PurchaseRequest::query()->where('user_id', $requester->id)->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function storePayload(User $director, array $overrides = []): array
    {
        return array_merge([
            'area_key' => 'operaciones',
            'fecha_solicitud' => now()->toDateString(),
            'solicitud_para' => 'Interno',
            'urgente' => false,
            'aprobador_id' => $director->id,
            'items' => [[
                'cantidad' => 1,
                'descripcion' => 'Monitor',
                'referencia' => 'Dell',
                'utilizacion' => 'Oficina',
                'ubicacion' => 'Cali',
            ]],
        ], $overrides);
    }

    private function downloadUrl(
        PurchaseRequest $purchaseRequest,
        PurchaseRequestAttachment $attachment,
        string $module = 'operaciones',
    ): string {
        return route('purchase-requests.attachments.download', [
            'module' => $module,
            'purchase_request' => $purchaseRequest->id,
            'attachment' => $attachment->id,
        ]);
    }

    private function purchaseRequester(string $area, string $email = 'requester@test.local'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'area_key' => $area,
            'must_change_password' => false,
        ]);
        $user->givePermissionTo([
            'purchase.tab.create',
            'purchase.tab.my_requests',
            "view.board.{$area}.solicitudes_compra",
        ]);

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

    private function outsider(): User
    {
        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->givePermissionTo(['view.board.operaciones.solicitudes_compra']);

        return $user;
    }

    private function emailApprovalShowUrl(PurchaseRequest $purchaseRequest, User $director): string
    {
        return URL::temporarySignedRoute(
            'purchase-requests.email-approval.show',
            now()->addHour(),
            ['purchase_request' => $purchaseRequest->id, 'director' => $director->id],
        );
    }

    private function assertAuditDoesNotLeakAttachment(AuditLog $log, PurchaseRequestAttachment $attachment): void
    {
        $encoded = json_encode($log->toArray());

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($attachment->stored_path, $encoded);
        $this->assertStringNotContainsString($attachment->original_name, $encoded);
        $this->assertArrayNotHasKey('stored_path', $log->metadata);
        $this->assertArrayNotHasKey('original_name', $log->metadata);
    }
}
