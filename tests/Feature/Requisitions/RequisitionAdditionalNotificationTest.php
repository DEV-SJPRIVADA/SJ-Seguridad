<?php

namespace Tests\Feature\Requisitions;

use App\Mail\PersonalRequisitionManagementApprovalMail;
use App\Mail\PersonalRequisitionNotification;
use App\Mail\PersonalRequisitionStatusChangedMail;
use App\Models\CommercialClient;
use App\Models\NotificationEmail;
use App\Models\NotificationType;
use App\Models\PersonalRequisition;
use App\Models\RequisitionCity;
use App\Models\RequisitionClient;
use App\Models\RequisitionClientType;
use App\Models\RequisitionPosition;
use App\Models\RequisitionProgrammingType;
use App\Models\RequisitionRequestReason;
use App\Models\RequisitionUniform;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequisitionAdditionalNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_store_queues_additional_recipients_when_client_type_is_not_administrativos(): void
    {
        Mail::fake();
        $this->syncNotificationEmails();

        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $payload = $this->validPayload([
            'client_type_id' => $this->clientTypeId('Externo'),
            'request_reason_id' => RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['reemplazo'])->firstOrFail()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        Mail::assertQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('gh.notify@example.com');
        });
        Mail::assertQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('extra.ops@example.com');
        });
    }

    public function test_store_does_not_queue_additional_recipients_for_administrativos(): void
    {
        Mail::fake();
        $this->syncNotificationEmails();

        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $payload = $this->validPayload([
            'client_type_id' => $this->clientTypeId('Administrativos'),
            'request_reason_id' => RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['reemplazo'])->firstOrFail()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        Mail::assertQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('gh.notify@example.com');
        });
        Mail::assertNotQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('extra.ops@example.com');
        });
    }

    public function test_cargo_nuevo_does_not_add_additional_to_management_approval_mail(): void
    {
        Mail::fake();
        $this->syncNotificationEmails();

        $user = User::factory()->create([
            'area_key' => 'operaciones',
            'must_change_password' => false,
        ]);
        $user->assignRole('usuario');
        $user->givePermissionTo('requisitions.tab.solicitar');

        $payload = $this->validPayload([
            'client_type_id' => $this->clientTypeId('Externo'),
            'request_reason_id' => RequisitionRequestReason::query()->whereRaw('LOWER(name) = ?', ['cargo nuevo'])->firstOrFail()->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post(route('requisitions.store', ['module' => 'operaciones']), $payload);

        Mail::assertSent(PersonalRequisitionManagementApprovalMail::class, function ($mail) {
            return $mail->hasTo('gerencia@example.com') && ! $mail->hasTo('extra.ops@example.com');
        });
        Mail::assertQueued(PersonalRequisitionNotification::class, function (PersonalRequisitionNotification $mail) {
            return $mail->hasTo('extra.ops@example.com');
        });
    }

    public function test_gestion_status_change_queues_additional_and_requester(): void
    {
        Mail::fake();
        $this->syncNotificationEmails();

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'email' => 'solicitante.extra@example.com',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo([
            'view.board.operaciones.requisiciones',
            'requisitions.tab.gestion',
        ]);

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionBaseAttributes($requester),
            [
                'code' => 'REQ-2026-ADD-01',
                'client_type_id' => $this->clientTypeId('Externo'),
                'status' => PersonalRequisition::STATUS_SOLICITADA,
            ]
        ));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(['client_type_id' => $this->clientTypeId('Externo')]),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
                'human_resources_observation' => 'En gestion.',
            ]
        ));

        Mail::assertQueued(PersonalRequisitionStatusChangedMail::class, function (PersonalRequisitionStatusChangedMail $mail) use ($requester) {
            return $mail->hasTo($requester->email);
        });
        Mail::assertQueued(PersonalRequisitionStatusChangedMail::class, function (PersonalRequisitionStatusChangedMail $mail) {
            return $mail->hasTo('extra.ops@example.com');
        });
    }

    public function test_gestion_status_change_skips_additional_for_administrativos(): void
    {
        Mail::fake();
        $this->syncNotificationEmails();

        $requester = User::factory()->create([
            'area_key' => 'operaciones',
            'email' => 'solicitante.admin@example.com',
            'must_change_password' => false,
        ]);
        $requester->assignRole('usuario');

        $manager = User::factory()->create([
            'area_key' => 'gestion_humana',
            'must_change_password' => false,
        ]);
        $manager->assignRole('usuario');
        $manager->givePermissionTo([
            'view.board.operaciones.requisiciones',
            'requisitions.tab.gestion',
        ]);

        $requisition = PersonalRequisition::create(array_merge(
            $this->requisitionBaseAttributes($requester),
            [
                'code' => 'REQ-2026-ADD-02',
                'client_type_id' => $this->clientTypeId('Administrativos'),
                'status' => PersonalRequisition::STATUS_SOLICITADA,
            ]
        ));
        $requisition->statusLogs()->create([
            'from_status' => null,
            'to_status' => PersonalRequisition::STATUS_SOLICITADA,
            'changed_by' => $requester->id,
        ]);

        $this->actingAs($manager)->patch(route('requisitions.update', ['module' => 'operaciones', 'requisition' => $requisition]), array_merge(
            $this->validPayload(['client_type_id' => $this->clientTypeId('Administrativos')]),
            [
                'status' => PersonalRequisition::STATUS_EN_GESTION,
            ]
        ));

        Mail::assertQueued(PersonalRequisitionStatusChangedMail::class, function (PersonalRequisitionStatusChangedMail $mail) use ($requester) {
            return $mail->hasTo($requester->email);
        });
        Mail::assertNotQueued(PersonalRequisitionStatusChangedMail::class, function (PersonalRequisitionStatusChangedMail $mail) {
            return $mail->hasTo('extra.ops@example.com');
        });
    }

    private function syncNotificationEmails(): void
    {
        $primary = NotificationEmail::query()->create([
            'name' => 'gh.notify@example.com',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $extra = NotificationEmail::query()->create([
            'name' => 'extra.ops@example.com',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $gerencia = NotificationEmail::query()->create([
            'name' => 'gerencia@example.com',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_NEW_REQUISITION)
            ->firstOrFail()
            ->notificationEmails()
            ->sync([$primary->id]);

        NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_REQUISITION_ADDITIONAL)
            ->firstOrFail()
            ->notificationEmails()
            ->sync([$extra->id]);

        NotificationType::query()
            ->where('module', NotificationType::MODULE_REQUISITIONS)
            ->where('slug', NotificationType::SLUG_MANAGEMENT_APPROVAL_CARGO_NUEVO)
            ->firstOrFail()
            ->notificationEmails()
            ->sync([$gerencia->id]);
    }

    private function clientTypeId(string $name): int
    {
        return (int) RequisitionClientType::query()->firstOrCreate(
            ['name' => $name],
            ['is_active' => true, 'sort_order' => 0]
        )->id;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'replacement_document' => 'Servicio nuevo',
            'replacement_name' => 'Servicio nuevo',
            'operating_area_key' => 'operaciones',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'commercial_client_id' => CommercialClient::query()->firstOrCreate(
                ['nit' => '900123456-1'],
                ['name' => 'Constructora Solanillas SAS', 'city' => 'Cali']
            )->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'client_type_id' => $this->clientTypeId('Externo'),
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'required_profile' => 'Perfil de prueba adicionales.',
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'service_structure' => 'Estructura de servicio de prueba.',
            'cost_center' => 'CC-001',
            'requester_observation' => 'Observacion.',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function requisitionBaseAttributes(User $requester): array
    {
        return [
            'requested_by' => $requester->id,
            'request_date' => now()->toDateString(),
            'leader_name' => $requester->name,
            'requesting_area_key' => 'operaciones',
            'position_id' => RequisitionPosition::query()->firstOrFail()->id,
            'sex' => 'masculino',
            'quantity' => 1,
            'operating_area_key' => 'operaciones',
            'request_reason_id' => RequisitionRequestReason::query()->firstOrFail()->id,
            'client_id' => RequisitionClient::query()->firstOrFail()->id,
            'city_id' => RequisitionCity::query()->firstOrFail()->id,
            'programming_type_id' => RequisitionProgrammingType::query()->firstOrFail()->id,
            'uniform_id' => RequisitionUniform::query()->firstOrFail()->id,
            'required_profile' => 'Perfil estado adicional',
            'service_structure' => 'Estructura',
            'cost_center' => 'CC-TRACK',
            'status_changed_at' => now(),
        ];
    }
}
