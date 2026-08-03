<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateIndicatorAuditLogsCommand extends Command
{
    protected $signature = 'audit:migrate-indicator-logs
                            {--dry-run : Report rows without inserting}
                            {--force : Insert without confirmation prompt}';

    protected $description = 'Copy indicator_audit_logs into audit_logs (module=indicadores, area=operaciones)';

    public function handle(): int
    {
        if (! Schema::hasTable('indicator_audit_logs')) {
            $this->warn('Table indicator_audit_logs does not exist.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('audit_logs')) {
            $this->error('Table audit_logs does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $alreadyMigrated = AuditLog::query()
            ->forModule('indicadores')
            ->whereNotNull('metadata->migrated_from_indicator_id')
            ->pluck('metadata')
            ->map(fn (array $metadata) => (int) ($metadata['migrated_from_indicator_id'] ?? 0))
            ->filter()
            ->all();

        $query = DB::table('indicator_audit_logs')
            ->when($alreadyMigrated !== [], fn ($builder) => $builder->whereNotIn('id', $alreadyMigrated))
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No indicator audit rows pending migration.');

            return self::SUCCESS;
        }

        $this->info("Found {$total} row(s) to migrate.");

        if ($dryRun) {
            $this->comment('Dry-run mode: no rows inserted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Proceed with migration?', true)) {
            $this->comment('Migration cancelled.');

            return self::SUCCESS;
        }

        $migrated = 0;

        $query->chunkById(500, function ($rows) use (&$migrated): void {
            foreach ($rows as $row) {
                AuditLog::query()->create([
                    'module' => 'indicadores',
                    'area' => 'operaciones',
                    'user_id' => $row->user_id,
                    'event_type' => $row->event_type,
                    'action' => $row->action,
                    'auditable_type' => $row->auditable_type,
                    'auditable_id' => $row->auditable_id,
                    'old_values' => $this->decodeJson($row->old_values),
                    'new_values' => $this->decodeJson($row->new_values),
                    'metadata' => array_merge(
                        $this->decodeJson($row->metadata) ?? [],
                        ['migrated_from_indicator_id' => $row->id],
                    ),
                    'reason' => $row->reason,
                    'ip_address' => $row->ip_address,
                    'user_agent' => $row->user_agent,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                $migrated++;
            }
        });

        $this->info("Migrated {$migrated} row(s) into audit_logs.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
