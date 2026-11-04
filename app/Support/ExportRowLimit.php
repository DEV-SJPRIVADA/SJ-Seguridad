<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Tope de filas en exports Excel (evita materializar colecciones enormes).
 */
final class ExportRowLimit
{
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function count(Builder $query): int
    {
        return (int) (clone $query)->toBase()->getCountForPagination();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function exceeds(Builder $query, int $maxRows): bool
    {
        $maxRows = max(1, $maxRows);

        return self::count($query) > $maxRows;
    }

    public static function rejectionMessage(int $maxRows, int $found): string
    {
        return "El export supera el máximo de {$maxRows} filas ({$found} encontradas). "
            .'Acote los filtros e intente de nuevo.';
    }
}
