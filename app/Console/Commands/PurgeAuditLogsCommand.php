<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PurgeAuditLogsCommand extends Command
{
    protected $signature = 'audit:purge
                            {--months= : Override retention months from config}
                            {--dry-run : Report eligible rows without deleting}
                            {--force : Delete without confirmation prompt}';

    protected $description = 'Delete audit_logs older than the configured retention period';

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: config('audit.retention_months', 24));

        if ($months < 1) {
            $this->error('Retention months must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subMonths($months);
        $eligible = AuditLog::query()->where('created_at', '<', $cutoff)->count();

        $this->info("Cutoff: {$cutoff->toDateTimeString()} ({$months} month(s)).");
        $this->info("Eligible rows: {$eligible}.");

        if ($eligible === 0) {
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry-run mode: no rows deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete eligible audit rows?', false)) {
            $this->comment('Purge cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $batch = AuditLog::query()
                ->where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(1000)
                ->pluck('id');

            if ($batch->isEmpty()) {
                break;
            }

            $count = AuditLog::query()->whereIn('id', $batch)->delete();
            $deleted += $count;
            $this->line("Deleted {$count} row(s)…");
        } while ($batch->count() === 1000);

        $this->info("Purge complete. Deleted {$deleted} row(s).");

        return self::SUCCESS;
    }
}
