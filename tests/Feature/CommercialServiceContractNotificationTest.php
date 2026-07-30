<?php

namespace Tests\Feature;

use App\Mail\CommercialServiceContractExpiringDigestMail;
use App\Models\CommercialClient;
use App\Models\CommercialService;
use App\Models\NotificationEmail;
use App\Models\NotificationType;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommercialServiceContractNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_digest_includes_service_first_day_in_expiring_window(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $service = $this->createService($user, '2026-08-29');

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertSent(CommercialServiceContractExpiringDigestMail::class);
        $this->assertDatabaseHas('commercial_service_contract_notification_logs', [
            'commercial_service_id' => $service->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_digest_skips_service_still_in_window_next_day(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createService($user, '2026-08-29');

        Artisan::call('comercial:send-service-contract-notification-digest');
        Mail::assertSent(CommercialServiceContractExpiringDigestMail::class, 1);

        Carbon::setTestNow('2026-07-31 08:00:00');
        Mail::fake();

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_digest_excludes_expired_contract(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createService($user, '2026-07-29');

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertNothingSent();
    }

    public function test_digest_excludes_inactive_service(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createService($user, '2026-08-29', isActive: false);

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertNothingSent();
    }

    public function test_digest_excludes_service_without_contract_end(): void
    {
        Mail::fake();

        $user = User::factory()->create(['must_change_password' => false]);
        $client = CommercialClient::query()->create([
            'nit' => '900000002',
            'name' => 'Sin fecha SA',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_end' => null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertNothingSent();
    }

    public function test_digest_uses_notification_config_recipients(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createService($user, '2026-08-29');

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_COMERCIAL)
            ->where('slug', NotificationType::SLUG_SERVICE_CONTRACT_EXPIRING)
            ->firstOrFail();

        $email = NotificationEmail::query()->create([
            'name' => 'comercial.contracts@example.com',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $type->notificationEmails()->attach($email->id);

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertSent(CommercialServiceContractExpiringDigestMail::class, function (CommercialServiceContractExpiringDigestMail $mail): bool {
            return $mail->hasTo('comercial.contracts@example.com');
        });

        Carbon::setTestNow();
    }

    public function test_digest_falls_back_when_no_recipients(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createService($user, '2026-08-29');

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_COMERCIAL)
            ->where('slug', NotificationType::SLUG_SERVICE_CONTRACT_EXPIRING)
            ->firstOrFail();
        $type->notificationEmails()->detach();

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertSent(CommercialServiceContractExpiringDigestMail::class, function (CommercialServiceContractExpiringDigestMail $mail): bool {
            return $mail->hasTo('desarrollo.tic@sjsp.com.co');
        });

        Carbon::setTestNow();
    }

    public function test_dry_run_does_not_send_mail_or_persist_logs(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createService($user, '2026-08-29');

        Artisan::call('comercial:send-service-contract-notification-digest', ['--dry-run' => true]);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('commercial_service_contract_notification_logs', 0);

        Carbon::setTestNow();
    }

    public function test_new_contract_end_starts_new_cycle(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $service = $this->createService($user, '2026-08-29');

        Artisan::call('comercial:send-service-contract-notification-digest');
        Mail::assertSent(CommercialServiceContractExpiringDigestMail::class, 1);

        $service->update(['contract_end' => '2026-09-15']);
        Carbon::setTestNow('2026-08-20 08:00:00');
        Mail::fake();

        Artisan::call('comercial:send-service-contract-notification-digest');

        Mail::assertSent(CommercialServiceContractExpiringDigestMail::class, 1);
        $this->assertDatabaseCount('commercial_service_contract_notification_logs', 2);

        Carbon::setTestNow();
    }

    private function createService(User $user, string $contractEnd, bool $isActive = true): CommercialService
    {
        $client = CommercialClient::query()->create([
            'nit' => (string) random_int(100000000, 999999999),
            'name' => 'Cliente Prueba SA',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return CommercialService::query()->create([
            'commercial_client_id' => $client->id,
            'portfolio' => CommercialService::PORTFOLIO_SEG_FISICA,
            'contract_number' => 'SJ2026-TEST',
            'is_active' => $isActive,
            'contract_end' => $contractEnd,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
