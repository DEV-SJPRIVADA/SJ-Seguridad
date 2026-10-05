<?php

namespace App\Support;

use RuntimeException;

/**
 * Directorio temporal escribible para PhpWord/cartas.
 * Prioriza sys_get_temp_dir() (comportamiento histórico que ya funcionaba);
 * solo si falla, usa rutas bajo storage.
 */
final class WordTempDirectory
{
    private static ?string $resolvedBase = null;

    public static function path(?string $subdir = null): string
    {
        $base = self::resolveBase();

        if ($subdir !== null && $subdir !== '') {
            $base .= DIRECTORY_SEPARATOR.trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subdir), DIRECTORY_SEPARATOR);
            self::ensureDirectory($base);
        }

        return $base;
    }

    public static function uniqueDir(string $prefix): string
    {
        $dir = self::path().DIRECTORY_SEPARATOR.$prefix.uniqid('', true);
        self::ensureDirectory($dir);

        return $dir;
    }

    private static function resolveBase(): string
    {
        if (self::$resolvedBase !== null) {
            return self::$resolvedBase;
        }

        foreach (self::candidates() as $candidate) {
            if (self::tryEnsureDirectory($candidate)) {
                self::$resolvedBase = $candidate;

                return self::$resolvedBase;
            }
        }

        throw new RuntimeException(
            'No se pudo crear un directorio temporal escribible para plantillas Word.',
        );
    }

    /**
     * @return list<string>
     */
    private static function candidates(): array
    {
        return [
            // Histórico / estable en el servidor Linux.
            rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'sjseguridad-phpword',
            storage_path('framework/cache/phpword'),
            storage_path('app/private/phpword-tmp'),
        ];
    }

    private static function ensureDirectory(string $directory): void
    {
        if (! self::tryEnsureDirectory($directory)) {
            throw new RuntimeException('No se pudo crear el directorio temporal: '.$directory);
        }
    }

    private static function tryEnsureDirectory(string $directory): bool
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        if (! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return false;
        }

        return is_writable($directory);
    }
}
