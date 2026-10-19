<?php

namespace App\Services\GestionHumana;

use Illuminate\Http\Request;

/**
 * Mantiene en sesión los IDs editados desde un listado filtrado de Acreditados.
 *
 * La lista se muestra como "(filtros actuales) OR id IN (IDs fijados)". Así el
 * registro editado sigue visible aunque el cambio lo saque del filtro. Los IDs
 * se limpian al cambiar filtros, al recargar la página o al pulsar "Actualizar vista".
 */
final class AcreditacionAcreditadoWorkingSetService
{
    private const SESSION_KEY = 'acreditaciones.acreditados.working_set';

    public function isActive(Request $request): bool
    {
        return (string) $request->input('working', '') === '1';
    }

    /**
     * @param  array<string, string>  $filterQuery
     * @return list<int>
     */
    public function pinnedIds(Request $request, array $filterQuery): array
    {
        if (! $this->isActive($request)) {
            return [];
        }

        if ($filterQuery === []) {
            $this->forget($request);

            return [];
        }

        $state = $request->session()->get(self::SESSION_KEY);
        if (! is_array($state)) {
            return [];
        }

        if (! $this->matches($state, $filterQuery)) {
            // La sesión solo guarda un conjunto de trabajo; si cambia la firma de filtros, se descarta.
            $this->forget($request);

            return [];
        }

        if (($state['updated_at'] ?? 0) < now()->subSeconds($this->ttlSeconds())->timestamp) {
            $this->forget($request);

            return [];
        }

        return $this->normalizeIds($state['ids'] ?? []);
    }

    /**
     * @param  array<string, string>  $filterQuery
     * @param  int|list<int>  $ids
     */
    public function pin(Request $request, array $filterQuery, int|array $ids): void
    {
        if (! $this->isActive($request) || $filterQuery === []) {
            return;
        }

        $state = $request->session()->get(self::SESSION_KEY);
        $existing = is_array($state) && $this->matches($state, $filterQuery)
            ? $this->normalizeIds($state['ids'] ?? [])
            : [];

        $merged = array_values(array_unique([
            ...$existing,
            ...$this->normalizeIds(is_array($ids) ? $ids : [$ids]),
        ]));

        $maxIds = $this->maxIds();
        if (count($merged) > $maxIds) {
            $merged = array_slice($merged, -$maxIds);
        }

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => (int) auth()->id(),
            'hash' => $this->hash($filterQuery),
            'ids' => $merged,
            'updated_at' => now()->timestamp,
        ]);
    }

    public function unpin(Request $request, int $id): void
    {
        $state = $request->session()->get(self::SESSION_KEY);
        if (! is_array($state)) {
            return;
        }

        $remaining = array_values(array_filter(
            $this->normalizeIds($state['ids'] ?? []),
            fn (int $value): bool => $value !== $id,
        ));

        if ($remaining === []) {
            $this->forget($request);

            return;
        }

        $state['ids'] = $remaining;
        $request->session()->put(self::SESSION_KEY, $state);
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<string, string>  $filterQuery
     */
    private function matches(array $state, array $filterQuery): bool
    {
        return ($state['user_id'] ?? null) === (int) auth()->id()
            && ($state['hash'] ?? '') === $this->hash($filterQuery);
    }

    /**
     * @param  array<string, string>  $filterQuery
     */
    private function hash(array $filterQuery): string
    {
        ksort($filterQuery);

        return sha1(http_build_query($filterQuery));
    }

    /**
     * @return list<int>
     */
    private function normalizeIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        $normalized = [];

        foreach ($ids as $id) {
            $value = (int) $id;
            if ($value > 0) {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }

    private function ttlSeconds(): int
    {
        return max(60, (int) config('acreditaciones.limits.working_set_ttl_seconds', 28800));
    }

    private function maxIds(): int
    {
        return max(1, (int) config('acreditaciones.limits.working_set_max_ids', 500));
    }
}
