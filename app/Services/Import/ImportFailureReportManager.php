<?php

namespace App\Services\Import;

use App\Exports\ImportFailureReportExport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportFailureReportManager
{
    private const CACHE_PREFIX = 'import_failure_report:';

    private const TTL_SECONDS = 86400;

    /**
     * @param  list<array<string, mixed>>  $failures
     * @param  array<string, int|string>  $summary
     * @param  list<string>  $rawColumnKeys
     */
    public function store(
        User $user,
        string $module,
        array $failures,
        array $summary,
        string $moduleTitle,
        array $rawColumnKeys,
        string $fileNamePrefix,
    ): ?string {
        if ($failures === []) {
            return null;
        }

        $token = Str::uuid()->toString();
        $directory = storage_path('app/import-reports/'.$user->id);
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.$token.'.xlsx';
        $export = new ImportFailureReportExport($failures, $summary, $moduleTitle, $rawColumnKeys);
        $export->saveToPath($path);

        Cache::put(self::CACHE_PREFIX.$token, [
            'user_id' => $user->id,
            'module' => $module,
            'path' => $path,
            'file_name' => $fileNamePrefix.'_'.now()->format('Y-m-d_His').'.xlsx',
        ], self::TTL_SECONDS);

        return $token;
    }

    public function download(User $user, string $token, string $expectedModule): StreamedResponse
    {
        $meta = Cache::get(self::CACHE_PREFIX.$token);

        if (! is_array($meta)) {
            abort(404, 'El reporte de importacion no existe o expiro.');
        }

        if ((int) ($meta['user_id'] ?? 0) !== $user->id) {
            abort(403);
        }

        if (($meta['module'] ?? '') !== $expectedModule) {
            abort(404);
        }

        $path = (string) ($meta['path'] ?? '');
        if ($path === '' || ! is_file($path)) {
            abort(404, 'El archivo del reporte ya no esta disponible.');
        }

        $fileName = (string) ($meta['file_name'] ?? 'reporte_importacion.xlsx');

        return response()->streamDownload(function () use ($path): void {
            readfile($path);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
