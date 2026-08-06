<?php

namespace App\Support;

final class ImportFailureRow
{
    public const SEVERITY_ERROR = 'error';

    public const SEVERITY_SKIPPED = 'skipped';

    public const SEVERITY_EMPTY = 'empty';

    /**
     * @param  array<string, mixed>  $raw
     * @return array{
     *     row: int,
     *     identifier: string|null,
     *     identifier_label: string,
     *     severity: string,
     *     reason: string,
     *     raw: array<string, mixed>
     * }
     */
    public static function make(
        int $row,
        ?string $identifier,
        string $identifierLabel,
        string $severity,
        string $reason,
        array $raw = [],
    ): array {
        return [
            'row' => $row,
            'identifier' => $identifier !== null && $identifier !== '' ? $identifier : null,
            'identifier_label' => $identifierLabel,
            'severity' => $severity,
            'reason' => $reason,
            'raw' => $raw,
        ];
    }

    /**
     * @param  array<string, mixed>  $failure
     */
    public static function message(array $failure): string
    {
        $row = (int) ($failure['row'] ?? 0);
        $identifier = $failure['identifier'] ?? null;
        $reason = (string) ($failure['reason'] ?? 'Error desconocido');
        $suffix = $identifier !== null && $identifier !== ''
            ? ' ('.($failure['identifier_label'] ?? 'ID').' '.$identifier.')'
            : '';

        return "Fila {$row}{$suffix}: {$reason}";
    }
}
