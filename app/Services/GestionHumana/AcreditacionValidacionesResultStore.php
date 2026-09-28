<?php

namespace App\Services\GestionHumana;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class AcreditacionValidacionesResultStore
{
    public const COLA_SIN_ACREDITACION = 'sin_acreditacion';

    public const COLA_AUSENTE_REPORTE = 'ausente_reporte';

    public const COLA_EN_PROCESO_YA_ACREDITADO = 'en_proceso_ya_acreditado';

    public const COLA_VENCIDAS = 'vencidas';

    /**
     * @var list<string>
     */
    public const COLAS = [
        self::COLA_SIN_ACREDITACION,
        self::COLA_AUSENTE_REPORTE,
        self::COLA_EN_PROCESO_YA_ACREDITADO,
        self::COLA_VENCIDAS,
    ];

    /**
     * @param  array{
     *     fecha_reporte: string,
     *     counts: array<string, int>,
     *     colas: array<string, list<array<string, mixed>>>
     * }  $payload
     * @return array{
     *     run_token: string,
     *     fecha_reporte: string,
     *     user_id: int,
     *     created_at: string,
     *     counts: array<string, int>,
     *     colas: array<string, list<array<string, mixed>>>
     * }
     */
    public function put(int $userId, string $fechaReporte, array $payload): array
    {
        $token = (string) Str::uuid();
        $stored = [
            'run_token' => $token,
            'fecha_reporte' => $fechaReporte,
            'user_id' => $userId,
            'created_at' => now()->toIso8601String(),
            'counts' => $payload['counts'],
            'colas' => $payload['colas'],
        ];

        Cache::put(
            $this->cacheKey($userId, $fechaReporte, $token),
            $stored,
            $this->ttlSeconds(),
        );

        return $stored;
    }

    /**
     * @return array{
     *     run_token: string,
     *     fecha_reporte: string,
     *     user_id: int,
     *     created_at: string,
     *     counts: array<string, int>,
     *     colas: array<string, list<array<string, mixed>>>
     * }|null
     */
    public function get(int $userId, string $fechaReporte, string $runToken): ?array
    {
        $runToken = trim($runToken);
        if ($runToken === '') {
            return null;
        }

        $payload = Cache::get($this->cacheKey($userId, $fechaReporte, $runToken));

        if (! is_array($payload)) {
            return null;
        }

        if ((int) ($payload['user_id'] ?? 0) !== $userId) {
            return null;
        }

        if ((string) ($payload['fecha_reporte'] ?? '') !== $fechaReporte) {
            return null;
        }

        if ((string) ($payload['run_token'] ?? '') !== $runToken) {
            return null;
        }

        return $payload;
    }

    public function isValidCola(string $cola): bool
    {
        return in_array($cola, self::COLAS, true);
    }

    public function cacheKey(int $userId, string $fechaReporte, string $runToken): string
    {
        return sprintf('acreditaciones:validaciones:%d:%s:%s', $userId, $fechaReporte, $runToken);
    }

    public function ttlSeconds(): int
    {
        return (int) config('acreditaciones.validaciones.cache_ttl_seconds', 5400);
    }
}
