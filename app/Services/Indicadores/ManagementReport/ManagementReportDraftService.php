<?php

namespace App\Services\Indicadores\ManagementReport;

use App\Models\ManagementReportDraft;
use App\Models\User;

class ManagementReportDraftService
{
    public function getDraft(int $year, int $month): ?ManagementReportDraft
    {
        return ManagementReportDraft::query()
            ->where(['year' => $year, 'month' => $month])
            ->first();
    }

    /**
     * @param  array<string, string>  $narratives
     */
    public function saveDraft(
        int $year,
        int $month,
        User $user,
        ?string $reportTitle,
        array $narratives
    ): ManagementReportDraft {
        $draft = ManagementReportDraft::query()->firstOrNew(['year' => $year, 'month' => $month]);

        $draft->report_title = $reportTitle;
        $draft->narratives = $narratives;
        $draft->updated_by_user_id = $user->id;
        $draft->save();

        return $draft;
    }

    public function clearDraft(int $year, int $month): void
    {
        ManagementReportDraft::query()
            ->where(['year' => $year, 'month' => $month])
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    public function applyDraftToReport(array $report, ?ManagementReportDraft $draft): array
    {
        if (! $draft instanceof ManagementReportDraft) {
            return $report;
        }

        if (is_string($draft->report_title) && trim($draft->report_title) !== '') {
            $report['report_title'] = $draft->report_title;
        }

        $narratives = $draft->narratives ?? [];

        foreach ($narratives as $code => $narrative) {
            if (! isset($report['indicators'][$code])) {
                continue;
            }

            if (! is_string($narrative) || trim($narrative) === '') {
                continue;
            }

            $report['indicators'][$code]['narrative'] = $narrative;
        }

        return $report;
    }
}
