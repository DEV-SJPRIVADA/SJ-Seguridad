<?php

namespace App\Support;

use RuntimeException;

/**
 * Directorio temporal bajo storage/app (Hostinger/open_basedir suele fallar con sys_get_temp_dir).
 */
final class WordTempDirectory
{
    public static function path(?string $subdir = null): string
    {
        $base = storage_path('app/tmp/phpword');

        if ($subdir !== null && $subdir !== '') {
            $base .= DIRECTORY_SEPARATOR.trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subdir), DIRECTORY_SEPARATOR);
        }

        if (! is_dir($base) && ! mkdir($base, 0775, true) && ! is_dir($base)) {
            throw new RuntimeException('No se pudo crear el directorio temporal para plantillas Word.');
        }

        return $base;
    }

    public static function uniqueDir(string $prefix): string
    {
        $dir = self::path().DIRECTORY_SEPARATOR.$prefix.uniqid('', true);

        if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException('No se pudo crear el directorio temporal de trabajo.');
        }

        return $dir;
    }
}
