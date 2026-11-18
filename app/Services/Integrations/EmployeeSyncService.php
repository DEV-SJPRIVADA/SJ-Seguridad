<?php

namespace App\Services\Integrations;

use App\Models\EmployeeFichaProfile;
use App\Models\IntegrationSyncLog;
use App\Models\IntegrationSyncState;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Productor de la integracion de empleados hacia SJ LegalSuite.
 *
 * Reglas fijas:
 * - Solo lectura de `employee_ficha_profiles` (no observers, no hooks de guardado).
 * - Whitelist estricta de campos: nada de datos de emergencia ni department_area.
 * - Los codigos DANE de ciudad viajan tal cual (5 digitos). La validacion contra
 *   `colombian_municipalities` y la derivacion del departamento ocurren en LegalSuite,
 *   porque StatFlow no tiene ese catalogo.
 */
class EmployeeSyncService
{
    public const STATE_KEY = 'employees';

    /** Codigos de documento que LegalSuite sabe mapear. */
    private const SENT_DOCUMENT_TYPES = ['CEDULA', 'C', 'CE', 'TARJETA EXTRANJERIA', 'NIT', 'N', 'TI', 'PT'];

    public function state(): IntegrationSyncState
    {
        return IntegrationSyncState::query()->firstOrCreate(
            ['key' => self::STATE_KEY],
            ['last_status' => 'never', 'last_sent' => 0, 'last_failed' => 0],
        );
    }

    public function batchSize(): int
    {
        return max(1, (int) config('integrations.legal_suite.batch_size', 500));
    }

    public function enabled(): bool
    {
        return filter_var(config('integrations.legal_suite.enabled', false), FILTER_VALIDATE_BOOL);
    }

    /**
     * Variables de entorno faltantes para poder enviar.
     *
     * @return list<string>
     */
    public function missingConfiguration(): array
    {
        $missing = [];

        if (! filled(config('integrations.legal_suite.url'))) {
            $missing[] = 'INTEGRATION_LEGALSUITE_URL';
        }

        if (! filled(config('integrations.legal_suite.token'))) {
            $missing[] = 'INTEGRATION_LEGALSUITE_TOKEN';
        }

        if (! filled(config('integrations.legal_suite.secret'))) {
            $missing[] = 'INTEGRATION_LEGALSUITE_SECRET';
        }

        return $missing;
    }

    public function endpoint(): string
    {
        return rtrim((string) config('integrations.legal_suite.url'), '/').'/api/integraciones/empleados';
    }

    /**
     * Payload normalizado de un perfil. Devuelve null si no hay documento valido.
     *
     * @return array<string, mixed>|null
     */
    public function payloadFor(EmployeeFichaProfile $profile): ?array
    {
        $documentNumber = preg_replace('/\D+/', '', (string) ($profile->document_number ?? '')) ?? '';

        if ($documentNumber === '') {
            return null;
        }

        $isActive = (string) ($profile->employment_status ?? '') === EmployeeFichaProfile::STATUS_ACTIVO;
        $salary = $profile->salary;

        return [
            'id' => (int) $profile->id,
            'document_number' => $documentNumber,
            'document_type' => $this->documentType($profile->document_type),
            'first_name' => $this->joinName($profile->first_name, $profile->second_name),
            'last_name' => $this->joinName($profile->first_surname, $profile->second_surname),
            'gender' => $this->gender($profile->sex),
            'birth_date' => $this->dateValue($profile->birth_date),
            'address' => $this->nullableString($profile->address),
            'residence_municipality_code' => $this->municipalityCode($profile->residence_city_code),
            // StatFlow no tiene catalogo DANE: LegalSuite valida y deriva el departamento.
            'residence_department_code' => null,
            'municipality_code' => $this->municipalityCode($profile->work_city_code),
            'work_department_code' => null,
            'phone' => $this->nullableString($profile->phone),
            'email' => $this->nullableString($profile->email),
            'hired_at' => $this->dateValue($profile->hire_date),
            'contract_type' => $this->contractType($profile->contract_type_name),
            'job_title' => $this->truncate($this->nullableString($profile->position_name), 120),
            'position_code' => $this->truncate($this->nullableString($profile->position_code), 20),
            'base_salary' => $salary === null ? null : number_format((float) $salary, 2, '.', ''),
            'is_active' => $isActive,
            // termination_date usa 9999-12-31 como centinela en registros activos.
            'termination_at' => $isActive ? null : $this->dateValue($profile->termination_date),
        ];
    }

    /**
     * Ejecuta una corrida de sincronizacion.
     *
     * @return array{
     *     status: string,
     *     mode: string,
     *     total: int,
     *     batches: int,
     *     sent: int,
     *     failed: int,
     *     stats: array<string, int>,
     *     samples: array<int, array<string, mixed>>,
     *     message: ?string,
     *     max_updated_at: ?Carbon
     * }
     */
    public function run(string $mode, Carbon $watermark, ?int $limit, bool $dryRun): array
    {
        $filtered = EmployeeFichaProfile::query()
            ->when(
                $mode === 'incremental',
                fn (Builder $query) => $query->where('updated_at', '>=', $watermark->copy()->subMinute())
            );

        $stats = $this->stats($filtered);
        $batchSize = $this->batchSize();
        $total = $limit !== null && $limit > 0 ? min($stats['total'], $limit) : $stats['total'];
        $totalBatches = $total > 0 ? (int) ceil($total / $batchSize) : 0;

        $query = (clone $filtered)->orderBy('updated_at')->orderBy('id');

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        if ($dryRun) {
            return $this->dryRun($mode, $query, $total, $totalBatches, $stats);
        }

        return $this->send($mode, $query, $total, $totalBatches, $batchSize);
    }

    /**
     * @param  array<string, int>  $stats
     * @return array<string, mixed>
     */
    private function dryRun(string $mode, Builder $query, int $total, int $totalBatches, array $stats): array
    {
        $samples = [];

        foreach ((clone $query)->limit(5)->get() as $profile) {
            $payload = $this->payloadFor($profile);

            if ($payload !== null) {
                $samples[] = $payload;
            }
        }

        IntegrationSyncLog::query()->create([
            'mode' => 'dry-run',
            'started_at' => now(),
            'finished_at' => now(),
            'status' => 'dry-run',
            'records_sent' => 0,
            'records_failed' => 0,
            'batches' => 0,
            'message' => "Simulacion {$mode}: {$total} registros en {$totalBatches} lotes.",
        ]);

        return [
            'status' => 'dry-run',
            'mode' => $mode,
            'total' => $total,
            'batches' => $totalBatches,
            'sent' => 0,
            'failed' => 0,
            'stats' => $stats,
            'samples' => $samples,
            'message' => null,
            'max_updated_at' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function send(string $mode, Builder $query, int $total, int $totalBatches, int $batchSize): array
    {
        $runId = (string) Str::uuid();
        $startedAt = now();
        $sent = 0;
        $failed = 0;
        $batches = 0;
        $maxUpdatedAt = null;
        $buffer = [];
        $bufferMax = null;
        $status = 'ok';
        $message = null;

        try {
            $this->assertTransport();

            foreach ($query->cursor() as $profile) {
                $payload = $this->payloadFor($profile);

                if ($payload === null) {
                    $failed++;

                    continue;
                }

                $buffer[] = $payload;
                $bufferMax = $this->latest($bufferMax, $profile->updated_at);

                if (count($buffer) < $batchSize) {
                    continue;
                }

                $batches++;
                $result = $this->sendBatch($buffer, $runId, $mode, $batches, max($totalBatches, $batches));

                if (! $result['ok']) {
                    throw new RuntimeException((string) $result['message']);
                }

                $sent += count($buffer);
                $maxUpdatedAt = $this->latest($maxUpdatedAt, $bufferMax);
                $buffer = [];
                $bufferMax = null;
            }

            if ($buffer !== []) {
                $batches++;
                $result = $this->sendBatch($buffer, $runId, $mode, $batches, max($totalBatches, $batches));

                if (! $result['ok']) {
                    throw new RuntimeException((string) $result['message']);
                }

                $sent += count($buffer);
                $maxUpdatedAt = $this->latest($maxUpdatedAt, $bufferMax);
            }
        } catch (Throwable $exception) {
            $status = 'failed';
            $message = Str::limit($exception->getMessage(), 500);
        }

        IntegrationSyncLog::query()->create([
            'mode' => $mode,
            'started_at' => $startedAt,
            'finished_at' => now(),
            'status' => $status,
            'records_sent' => $sent,
            'records_failed' => $failed,
            'batches' => $batches,
            'message' => $message,
        ]);

        $this->persistState($status, $sent, $failed, $status === 'ok' ? $maxUpdatedAt : null);

        $context = [
            'run_id' => $runId,
            'mode' => $mode,
            'sent' => $sent,
            'failed' => $failed,
            'batches' => $batches,
        ];

        if ($status === 'ok') {
            Log::info('integracion.employees.ok', $context);
        } else {
            Log::warning('integracion.employees.failed', $context + ['message' => $message]);
        }

        return [
            'status' => $status,
            'mode' => $mode,
            'total' => $total,
            'batches' => $batches,
            'sent' => $sent,
            'failed' => $failed,
            'stats' => [],
            'samples' => [],
            'message' => $message,
            'max_updated_at' => $maxUpdatedAt,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array{ok: bool, message?: string, summary?: mixed}
     */
    private function sendBatch(array $records, string $runId, string $mode, int $batch, int $totalBatches): array
    {
        $body = json_encode([
            'run_id' => $runId,
            'mode' => $mode,
            'batch' => $batch,
            'total_batches' => $totalBatches,
            'sent_at' => now()->toIso8601String(),
            'records' => $records,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $headers = $this->signatureHeaders($body);

        try {
            $response = Http::withHeaders($headers)
                ->timeout((int) config('integrations.legal_suite.timeout', 60))
                ->retry(3, (int) config('integrations.legal_suite.retry_sleep_ms', 2000))
                ->withBody($body, 'application/json')
                ->post($this->endpoint());
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => "Lote {$batch} fallo: ".$exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => "Lote {$batch} respondio HTTP {$response->status()}: ".Str::limit((string) $response->body(), 300),
            ];
        }

        return ['ok' => true, 'summary' => $response->json()];
    }

    /**
     * Comprueba el canal contra LegalSuite enviando un lote vacio.
     *
     * No escribe empleados: valida URL, TLS, token, secret y reloj. Se puede
     * ejecutar antes de poner INTEGRATION_LEGALSUITE_ENABLED=true.
     *
     * @return array{ok: bool, status: int|null, body: mixed, error: string|null}
     */
    public function probe(): array
    {
        try {
            $this->assertTransport();
        } catch (RuntimeException $exception) {
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $exception->getMessage()];
        }

        $body = json_encode([
            'run_id' => (string) Str::uuid(),
            'mode' => 'probe',
            'batch' => 1,
            'total_batches' => 1,
            'sent_at' => now()->toIso8601String(),
            'records' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        try {
            // Sin reintentos: si falla, se quiere saber de inmediato.
            $response = Http::withHeaders($this->signatureHeaders($body))
                ->timeout((int) config('integrations.legal_suite.timeout', 60))
                ->withBody($body, 'application/json')
                ->post($this->endpoint());
        } catch (Throwable $exception) {
            return ['ok' => false, 'status' => null, 'body' => null, 'error' => $exception->getMessage()];
        }

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
            'error' => null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function signatureHeaders(string $body): array
    {
        return [
            'X-Integration-Key' => (string) config('integrations.legal_suite.token'),
            'X-Signature' => hash_hmac('sha256', $body, (string) config('integrations.legal_suite.secret')),
            'X-Timestamp' => (string) now()->getTimestamp(),
            'Accept' => 'application/json',
        ];
    }

    private function assertTransport(): void
    {
        $url = (string) config('integrations.legal_suite.url');

        $allowsLocalHttp = app()->environment('local')
            && filter_var(config('integrations.legal_suite.allow_http_local', false), FILTER_VALIDATE_BOOL);

        $isHttps = str_starts_with($url, 'https://');
        $isLocalHttp = $allowsLocalHttp && str_starts_with($url, 'http://');

        if (! $isHttps && ! $isLocalHttp) {
            throw new RuntimeException('INTEGRATION_LEGALSUITE_URL debe ser una URL HTTPS.');
        }

        $missing = $this->missingConfiguration();

        if ($missing !== []) {
            throw new RuntimeException('Faltan variables de entorno: '.implode(', ', $missing));
        }
    }

    /**
     * @return array<string, int>
     */
    private function stats(Builder $query): array
    {
        $base = (clone $query)->reorder();

        $missingPosition = (clone $base)
            ->where(fn (Builder $inner) => $inner->whereNull('position_code')->orWhere('position_code', ''))
            ->count();

        $missingEmail = (clone $base)
            ->where(fn (Builder $inner) => $inner->whereNull('email')->orWhere('email', ''))
            ->count();

        $unknownDocumentType = (clone $base)
            ->where(fn (Builder $inner) => $inner->whereNull('document_type')->orWhereNotIn('document_type', self::SENT_DOCUMENT_TYPES))
            ->count();

        return [
            'total' => (clone $base)->count(),
            'sin_position_code' => $missingPosition,
            'sin_email' => $missingEmail,
            'document_type_desconocido' => $unknownDocumentType,
        ];
    }

    private function persistState(string $status, int $sent, int $failed, ?Carbon $watermark): void
    {
        $state = $this->state();
        $state->last_run_at = now();
        $state->last_status = $status;
        $state->last_sent = $sent;
        $state->last_failed = $failed;

        if ($watermark !== null) {
            $state->last_watermark = $watermark;
        }

        $state->save();
    }

    private function latest(?Carbon $current, CarbonInterface|string|null $candidate): ?Carbon
    {
        if ($candidate === null || $candidate === '') {
            return $current;
        }

        try {
            $value = $candidate instanceof CarbonInterface
                ? Carbon::instance($candidate->toDateTime())
                : Carbon::parse((string) $candidate);
        } catch (Throwable) {
            return $current;
        }

        if ($current === null || $value->greaterThan($current)) {
            return $value;
        }

        return $current;
    }

    private function joinName(?string $first, ?string $second): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', trim((string) $first.' '.(string) $second)) ?? '');

        return $value === '' ? '-' : mb_substr($value, 0, 100);
    }

    private function documentType(?string $raw): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', mb_strtoupper((string) $raw)) ?? '');

        return $value === '' ? null : mb_substr($value, 0, 20);
    }

    private function gender(?string $sex): ?string
    {
        return match (mb_strtoupper(trim((string) $sex))) {
            'M' => 'masculino',
            'F' => 'femenino',
            default => null,
        };
    }

    private function contractType(?string $raw): ?string
    {
        $value = $this->normalize($raw ?? '');

        if ($value === '' || str_contains($value, 'no defin')) {
            return null;
        }

        if (str_contains($value, 'indefin')) {
            return 'termino_indefinido';
        }

        if (str_contains($value, 'obra')) {
            return 'obra_labor';
        }

        if (str_contains($value, 'lectiv')) {
            return 'aprendizaje_lectiva';
        }

        if (str_contains($value, 'practic') || str_contains($value, 'productiv')) {
            return 'aprendizaje_practica';
        }

        if (str_contains($value, 'aprendiz')) {
            return 'aprendizaje_lectiva';
        }

        if (str_contains($value, 'fijo')) {
            return 'termino_fijo';
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $normalized);

        return preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
    }

    private function municipalityCode(?string $code): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $code) ?? '';

        return strlen($digits) === 5 ? $digits : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = $value instanceof CarbonInterface ? $value : Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }

        // 9999-12-31 es el centinela de "sin desvinculacion" en la ficha.
        if ((int) $date->year >= 9000) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    private function nullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function truncate(?string $value, int $length): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }
}
