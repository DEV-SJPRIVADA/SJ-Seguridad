<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Services\Import\ImportFailureReportManager;
use App\Support\ImportFailureRow;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesImportFailureReports
{
    /**
     * @param  array<string, mixed>  $stats
     * @param  array<string, int|string>  $summary
     * @param  list<string>  $rawColumnKeys
     * @return array<string, mixed>
     */
    protected function buildImportResultPayload(
        User $user,
        array $stats,
        string $module,
        string $moduleTitle,
        array $summary,
        array $rawColumnKeys,
        string $fileNamePrefix,
    ): array {
        /** @var list<array<string, mixed>> $failures */
        $failures = $stats['failures'] ?? [];

        $reportFailures = array_values(array_filter(
            $failures,
            fn (array $failure): bool => ($failure['severity'] ?? '') !== ImportFailureRow::SEVERITY_EMPTY,
        ));

        $reportToken = app(ImportFailureReportManager::class)->store(
            $user,
            $module,
            $reportFailures,
            $summary,
            $moduleTitle,
            $rawColumnKeys,
            $fileNamePrefix,
        );

        $displayFailures = array_values(array_filter(
            $failures,
            fn (array $failure): bool => in_array($failure['severity'] ?? '', [
                ImportFailureRow::SEVERITY_ERROR,
                ImportFailureRow::SEVERITY_SKIPPED,
            ], true),
        ));

        $errors = array_map(
            fn (array $failure): string => ImportFailureRow::message($failure),
            $displayFailures,
        );

        return [
            ...$stats,
            'errors' => $errors,
            'failures_count' => count($displayFailures),
            'empty_rows' => (int) ($stats['empty_rows'] ?? 0),
            'report_token' => $reportToken,
            'errors_total' => count($errors),
            'errors_truncated' => false,
        ];
    }

    protected function downloadImportFailureReport(User $user, string $token, string $module): StreamedResponse
    {
        return app(ImportFailureReportManager::class)->download($user, $token, $module);
    }
}
