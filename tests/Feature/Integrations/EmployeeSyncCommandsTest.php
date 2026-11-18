<?php

namespace Tests\Feature\Integrations;

use App\Models\IntegrationSyncLog;
use App\Models\IntegrationSyncState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmployeeSyncCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'integrations.legal_suite.url' => 'https://legalsuite.test',
            'integrations.legal_suite.token' => 'token-de-prueba',
            'integrations.legal_suite.secret' => 'secreto-de-prueba',
            'integrations.legal_suite.enabled' => true,
            'integrations.legal_suite.retry_sleep_ms' => 0,
        ]);
    }

    private function insertProfiles(int $count, string $updatedAt = '2026-01-01 10:00:00'): void
    {
        $rows = [];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'document_number' => (string) (90000000 + $i),
                'document_type' => 'C',
                'first_name' => 'NOMBRE'.$i,
                'first_surname' => 'APELLIDO'.$i,
                'sex' => 'M',
                'birth_date' => '1990-01-01',
                'residence_city_code' => '76001',
                'email' => 'user'.$i.'@example.com',
                'hire_date' => '2020-01-01',
                'contract_type_name' => 'A TERMINO FIJO',
                'position_code' => '009',
                'position_name' => 'GUARDA',
                'salary' => '2000000.00',
                'employment_status' => 'activo',
                'created_at' => $updatedAt,
                'updated_at' => $updatedAt,
            ];
        }

        foreach (array_chunk($rows, 400) as $chunk) {
            DB::table('employee_ficha_profiles')->insert($chunk);
        }
    }

    private function seedState(string $watermark = '2026-01-01 09:00:00'): IntegrationSyncState
    {
        return IntegrationSyncState::query()->create([
            'key' => 'employees',
            'last_watermark' => $watermark,
            'last_status' => 'ok',
            'last_sent' => 0,
            'last_failed' => 0,
        ]);
    }

    public function test_incremental_envia_y_actualiza_el_watermark(): void
    {
        Http::fake(['*' => Http::response(['created' => 2, 'updated' => 0, 'skipped' => 0, 'errors' => []], 200)]);

        $this->insertProfiles(2);
        $this->seedState();

        $this->artisan('integracion:push-cambios')->assertExitCode(0);

        Http::assertSentCount(1);

        $state = IntegrationSyncState::query()->where('key', 'employees')->firstOrFail();

        $this->assertSame('ok', $state->last_status);
        $this->assertSame(2, $state->last_sent);
        $this->assertTrue($state->last_watermark->equalTo(Carbon::parse('2026-01-01 10:00:00')));

        $log = IntegrationSyncLog::query()->where('mode', 'incremental')->latest('id')->firstOrFail();
        $this->assertSame('ok', $log->status);
        $this->assertSame(2, $log->records_sent);
        $this->assertSame(1, $log->batches);
    }

    public function test_incremental_firmado_con_hmac_sobre_el_body_crudo(): void
    {
        Http::fake(['*' => Http::response(['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []], 200)]);

        $this->insertProfiles(1);
        $this->seedState();

        $this->artisan('integracion:push-cambios')->assertExitCode(0);

        Http::assertSent(function (ClientRequest $request): bool {
            $body = $request->body();

            return $request->hasHeader('X-Integration-Key', 'token-de-prueba')
                && $request->hasHeader('X-Signature', hash_hmac('sha256', $body, 'secreto-de-prueba'))
                && $request->hasHeader('X-Timestamp')
                && $request->url() === 'https://legalsuite.test/api/integraciones/empleados';
        });
    }

    public function test_incremental_con_error_500_no_mueve_el_watermark(): void
    {
        Http::fake(['*' => Http::response(['message' => 'boom'], 500)]);

        $this->insertProfiles(2);
        $this->seedState('2026-01-01 09:00:00');

        $this->artisan('integracion:push-cambios')->assertExitCode(1);

        $state = IntegrationSyncState::query()->where('key', 'employees')->firstOrFail();

        $this->assertSame('failed', $state->last_status);
        $this->assertTrue($state->last_watermark->equalTo(Carbon::parse('2026-01-01 09:00:00')));
        $this->assertSame(0, $state->last_sent);

        $log = IntegrationSyncLog::query()->where('mode', 'incremental')->latest('id')->firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->message);
    }

    public function test_full_batchea_de_a_500_y_actualiza_el_watermark(): void
    {
        Http::fake(['*' => Http::response(['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []], 200)]);

        $this->insertProfiles(1201);
        $this->seedState('2025-12-01 00:00:00');

        $this->artisan('integracion:push-empleados --full')->assertExitCode(0);

        Http::assertSentCount(3);

        $batches = Http::recorded()
            ->map(fn (array $pair): array => [
                'batch' => $pair[0]->data()['batch'],
                'total' => $pair[0]->data()['total_batches'],
                'mode' => $pair[0]->data()['mode'],
                'records' => count($pair[0]->data()['records']),
            ])
            ->values()
            ->all();

        $this->assertSame([
            ['batch' => 1, 'total' => 3, 'mode' => 'full', 'records' => 500],
            ['batch' => 2, 'total' => 3, 'mode' => 'full', 'records' => 500],
            ['batch' => 3, 'total' => 3, 'mode' => 'full', 'records' => 201],
        ], $batches);

        $state = IntegrationSyncState::query()->where('key', 'employees')->firstOrFail();
        $this->assertSame(1201, $state->last_sent);
        $this->assertTrue($state->last_watermark->equalTo(Carbon::parse('2026-01-01 10:00:00')));
    }

    public function test_dry_run_no_hace_http(): void
    {
        Http::fake();

        $this->insertProfiles(3);
        $this->seedState();

        $this->artisan('integracion:push-cambios --dry-run')->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_no_envia_si_la_integracion_esta_deshabilitada(): void
    {
        Http::fake();
        config(['integrations.legal_suite.enabled' => false]);

        $this->insertProfiles(1);
        $this->seedState();

        $this->artisan('integracion:push-cambios')->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_full_exige_la_bandera_full(): void
    {
        Http::fake();

        $this->artisan('integracion:push-empleados')->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_permite_http_sin_ssl_solo_en_local_con_bandera_explicita(): void
    {
        Http::fake(['*' => Http::response(['created' => 1, 'updated' => 0, 'skipped' => 0, 'errors' => []], 200)]);

        $this->app['env'] = 'local';
        config([
            'integrations.legal_suite.url' => 'http://sjlegalsuite.test',
            'integrations.legal_suite.allow_http_local' => true,
        ]);

        $this->insertProfiles(1);
        $this->seedState();

        $this->artisan('integracion:push-cambios')->assertExitCode(0);

        Http::assertSentCount(1);
    }

    public function test_rechaza_http_fuera_de_local_aunque_la_bandera_este_activa(): void
    {
        Http::fake();

        $this->app['env'] = 'production';
        config([
            'integrations.legal_suite.url' => 'http://sjlegalsuite.test',
            'integrations.legal_suite.allow_http_local' => true,
        ]);

        $this->insertProfiles(1);
        $this->seedState();

        $this->artisan('integracion:push-cambios')->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_probar_envia_lote_vacio_y_valida_credenciales(): void
    {
        Http::fake(['*' => Http::response(['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []], 200)]);

        $this->artisan('integracion:probar')->assertExitCode(0);

        Http::assertSent(function (ClientRequest $request): bool {
            $payload = json_decode($request->body(), true);

            return $payload['records'] === []
                && $payload['mode'] === 'probe'
                && $request->hasHeader('X-Signature')
                && $request->hasHeader('X-Timestamp');
        });
    }

    public function test_probar_funciona_con_la_integracion_deshabilitada(): void
    {
        Http::fake(['*' => Http::response(['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []], 200)]);
        config(['integrations.legal_suite.enabled' => false]);

        $this->artisan('integracion:probar')->assertExitCode(0);

        Http::assertSentCount(1);
    }

    public function test_probar_falla_con_token_incorrecto(): void
    {
        Http::fake(['*' => Http::response(['ok' => false, 'message' => 'Token de integracion invalido.'], 401)]);

        $this->artisan('integracion:probar')->assertExitCode(1);
    }

    public function test_probar_no_envia_nada_si_la_url_no_es_https(): void
    {
        Http::fake();

        $this->app['env'] = 'production';
        config([
            'integrations.legal_suite.url' => 'http://legalsuite.test',
            'integrations.legal_suite.allow_http_local' => false,
        ]);

        $this->artisan('integracion:probar')->assertExitCode(1);

        Http::assertNothingSent();
    }
}
