<?php

namespace Tests\Feature;

use App\Mail\CommercialDocumentationDigestMail;
use App\Models\CommercialClient;
use App\Models\CommercialClientDocumentationNotificationLog;
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

class CommercialDocumentationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        PermissionCatalog::sync();
    }

    public function test_digest_includes_client_first_day_in_expiring_window(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $client = $this->createClient($user, '2026-08-29', 30);

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertSent(CommercialDocumentationDigestMail::class);
        $this->assertDatabaseHas('commercial_client_documentation_notification_logs', [
            'commercial_client_id' => $client->id,
            'alert_kind' => CommercialClientDocumentationNotificationLog::KIND_EXPIRING,
        ]);

        Carbon::setTestNow();
    }

    public function test_digest_skips_client_still_in_window_next_day(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createClient($user, '2026-08-29', 30);

        Artisan::call('comercial:send-documentation-notification-digest');
        Mail::assertSent(CommercialDocumentationDigestMail::class, 1);

        Carbon::setTestNow('2026-07-31 08:00:00');
        Mail::fake();

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_digest_notifies_once_when_documentation_becomes_expired(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $client = $this->createClient($user, '2026-08-29', 30);

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertSent(CommercialDocumentationDigestMail::class);
        $this->assertDatabaseHas('commercial_client_documentation_notification_logs', [
            'commercial_client_id' => $client->id,
            'alert_kind' => CommercialClientDocumentationNotificationLog::KIND_EXPIRED,
        ]);

        Carbon::setTestNow();
    }

    public function test_digest_excludes_client_without_expiry_date(): void
    {
        Mail::fake();

        $user = User::factory()->create(['must_change_password' => false]);
        CommercialClient::query()->create([
            'nit' => '900000001',
            'name' => 'Sin fecha SA',
            'documentation_expires_on' => null,
            'alert_days_before' => 30,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertNothingSent();
    }

    public function test_digest_uses_notification_config_recipients(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createClient($user, '2026-08-29', 30);

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_COMERCIAL)
            ->where('slug', NotificationType::SLUG_DOCUMENTATION_EXPIRING)
            ->firstOrFail();

        $email = NotificationEmail::query()->create([
            'name' => 'comercial.ops@example.com',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $type->notificationEmails()->attach($email->id);

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertSent(CommercialDocumentationDigestMail::class, function (CommercialDocumentationDigestMail $mail): bool {
            return $mail->hasTo('comercial.ops@example.com');
        });

        Carbon::setTestNow();
    }

    public function test_digest_falls_back_when_no_recipients(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createClient($user, '2026-08-29', 30);

        $type = NotificationType::query()
            ->where('module', NotificationType::MODULE_COMERCIAL)
            ->where('slug', NotificationType::SLUG_DOCUMENTATION_EXPIRING)
            ->firstOrFail();
        $type->notificationEmails()->detach();

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertSent(CommercialDocumentationDigestMail::class, function (CommercialDocumentationDigestMail $mail): bool {
            return $mail->hasTo('desarrollo.tic@sjsp.com.co');
        });

        Carbon::setTestNow();
    }

    public function test_dry_run_does_not_send_mail_or_persist_logs(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $this->createClient($user, '2026-08-29', 30);

        Artisan::call('comercial:send-documentation-notification-digest', ['--dry-run' => true]);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('commercial_client_documentation_notification_logs', 0);

        Carbon::setTestNow();
    }

    public function test_new_expiry_date_starts_new_cycle(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-30 08:00:00');

        $user = User::factory()->create(['must_change_password' => false]);
        $client = $this->createClient($user, '2026-08-29', 30);

        Artisan::call('comercial:send-documentation-notification-digest');
        Mail::assertSent(CommercialDocumentationDigestMail::class, 1);

        $client->update(['documentation_expires_on' => '2026-09-15']);
        Carbon::setTestNow('2026-08-20 08:00:00');
        Mail::fake();

        Artisan::call('comercial:send-documentation-notification-digest');

        Mail::assertSent(CommercialDocumentationDigestMail::class, 1);
        $this->assertDatabaseCount('commercial_client_documentation_notification_logs', 2);

        Carbon::setTestNow();
    }

    private function createClient(User $user, string $expiresOn, int $alertDays): CommercialClient
    {
        return CommercialClient::query()->create([
            'nit' => (string) random_int(100000000, 999999999),
            'name' => 'Cliente Prueba SA',
            'documentation_expires_on' => $expiresOn,
            'alert_days_before' => $alertDays,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }
}
