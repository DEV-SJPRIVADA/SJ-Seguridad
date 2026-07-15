<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            $this->migrateSqlite();

            return;
        }

        $this->migrateMysql();
    }

    public function down(): void
    {
        Schema::create('operations_leaders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('indicator_captures', function (Blueprint $table) {
            $table->dropUnique('indicator_captures_unique');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('operations_leader_id')->nullable()->constrained('operations_leaders')->nullOnDelete();
            $table->unique(['indicator_id', 'operations_leader_id', 'period_id'], 'indicator_captures_unique');
        });

        Schema::table('indicator_improvements', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('operations_leader_id')->nullable()->constrained('operations_leaders')->nullOnDelete();
        });
    }

    private function migrateMysql(): void
    {
        if (! Schema::hasColumn('indicator_captures', 'user_id')) {
            Schema::table('indicator_captures', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('indicator_id');
            });
        }

        if (! Schema::hasColumn('indicator_improvements', 'user_id')) {
            Schema::table('indicator_improvements', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->after('indicator_id');
            });
        }

        DB::table('indicator_captures')
            ->whereNull('user_id')
            ->whereNotNull('created_by_user_id')
            ->update(['user_id' => DB::raw('created_by_user_id')]);

        DB::table('indicator_improvements')
            ->whereNull('user_id')
            ->whereNotNull('created_by_user_id')
            ->update(['user_id' => DB::raw('created_by_user_id')]);

        DB::table('indicator_improvements')->whereNull('user_id')->delete();
        DB::table('indicator_captures')->whereNull('user_id')->delete();

        if (Schema::hasColumn('indicator_captures', 'operations_leader_id')) {
            $indexes = $this->indexNames('indicator_captures');

            if (! in_array('indicator_captures_indicator_id_index', $indexes, true)) {
                Schema::table('indicator_captures', function (Blueprint $table) {
                    $table->index('indicator_id', 'indicator_captures_indicator_id_index');
                });
            }

            if ($this->foreignKeyExists('indicator_captures', 'indicator_captures_operations_leader_id_foreign')) {
                Schema::table('indicator_captures', function (Blueprint $table) {
                    $table->dropForeign(['operations_leader_id']);
                });
            } elseif (in_array('indicator_captures_operations_leader_id_foreign', $indexes, true)) {
                Schema::table('indicator_captures', function (Blueprint $table) {
                    $table->dropIndex('indicator_captures_operations_leader_id_foreign');
                });
            }

            if (in_array('indicator_captures_unique', $indexes, true)) {
                Schema::table('indicator_captures', function (Blueprint $table) {
                    $table->dropUnique('indicator_captures_unique');
                });
            }

            Schema::table('indicator_captures', function (Blueprint $table) {
                $table->dropColumn('operations_leader_id');
            });
        }

        if (Schema::hasColumn('indicator_improvements', 'operations_leader_id')) {
            $improvementIndexes = $this->indexNames('indicator_improvements');

            if ($this->foreignKeyExists('indicator_improvements', 'indicator_improvements_operations_leader_id_foreign')) {
                Schema::table('indicator_improvements', function (Blueprint $table) {
                    $table->dropForeign(['operations_leader_id']);
                });
            } elseif (in_array('indicator_improvements_operations_leader_id_foreign', $improvementIndexes, true)) {
                Schema::table('indicator_improvements', function (Blueprint $table) {
                    $table->dropIndex('indicator_improvements_operations_leader_id_foreign');
                });
            }

            Schema::table('indicator_improvements', function (Blueprint $table) {
                $table->dropColumn('operations_leader_id');
            });
        }

        $this->ensureForeignKey('indicator_captures', 'user_id', 'indicator_captures_user_id_foreign');
        $this->ensureForeignKey('indicator_improvements', 'user_id', 'indicator_improvements_user_id_foreign');

        if (! in_array('indicator_captures_unique', $this->indexNames('indicator_captures'), true)) {
            Schema::table('indicator_captures', function (Blueprint $table) {
                $table->unique(['indicator_id', 'user_id', 'period_id'], 'indicator_captures_unique');
            });
        }

        Schema::dropIfExists('operations_leaders');
        Schema::dropIfExists('indicator_system_document_versions');
        Schema::dropIfExists('indicator_system_documents');
    }

    private function migrateSqlite(): void
    {
        Schema::dropIfExists('indicator_improvements');
        Schema::dropIfExists('indicator_captures');
        Schema::dropIfExists('operations_leaders');
        Schema::dropIfExists('indicator_system_document_versions');
        Schema::dropIfExists('indicator_system_documents');

        Schema::create('indicator_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('indicator_periods')->cascadeOnDelete();
            $table->json('input_data');
            $table->decimal('numerator', 14, 2);
            $table->decimal('denominator', 14, 2);
            $table->decimal('result_percentage', 8, 2);
            $table->boolean('complies');
            $table->longText('analysis_text')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['indicator_id', 'user_id', 'period_id'], 'indicator_captures_unique');
        });

        Schema::create('indicator_improvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_capture_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('indicator_periods')->cascadeOnDelete();
            $table->longText('analysis');
            $table->longText('action_taken');
            $table->longText('action_defined');
            $table->longText('improvement_required')->nullable();
            $table->longText('integrated_analysis_block')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('indicator_capture_id');
        });
    }

    /**
     * @return list<string>
     */
    private function indexNames(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    private function ensureForeignKey(string $table, string $column, string $constraintName): void
    {
        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->foreign($column)->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $constraintName, 'FOREIGN KEY']
        ))->isNotEmpty();
    }
};
