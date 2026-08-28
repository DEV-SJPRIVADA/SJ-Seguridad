<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('personal_requisitions')) {
            Schema::table('personal_requisitions', function (Blueprint $table): void {
                if (! Schema::hasColumn('personal_requisitions', 'hired_first_surname')) {
                    $table->string('hired_first_surname', 100)->nullable()->after('hired_document');
                }
                if (! Schema::hasColumn('personal_requisitions', 'hired_second_surname')) {
                    $table->string('hired_second_surname', 100)->nullable()->after('hired_first_surname');
                }
                if (! Schema::hasColumn('personal_requisitions', 'hired_first_name')) {
                    $table->string('hired_first_name', 100)->nullable()->after('hired_second_surname');
                }
                if (! Schema::hasColumn('personal_requisitions', 'hired_second_name')) {
                    $table->string('hired_second_name', 100)->nullable()->after('hired_first_name');
                }
            });
        }

        if (Schema::hasTable('personal_requisition_ficha_entries')) {
            Schema::table('personal_requisition_ficha_entries', function (Blueprint $table): void {
                if (! Schema::hasColumn('personal_requisition_ficha_entries', 'first_surname')) {
                    $table->string('first_surname', 100)->nullable()->after('hired_document');
                }
                if (! Schema::hasColumn('personal_requisition_ficha_entries', 'second_surname')) {
                    $table->string('second_surname', 100)->nullable()->after('first_surname');
                }
                if (! Schema::hasColumn('personal_requisition_ficha_entries', 'first_name')) {
                    $table->string('first_name', 100)->nullable()->after('second_surname');
                }
                if (! Schema::hasColumn('personal_requisition_ficha_entries', 'second_name')) {
                    $table->string('second_name', 100)->nullable()->after('first_name');
                }
            });
        }

        // Backfill existing records from profile if available
        if (Schema::hasTable('employee_ficha_profiles') && Schema::hasTable('personal_requisition_ficha_entries')) {
            $entries = DB::table('personal_requisition_ficha_entries')
                ->join('employee_ficha_profiles', 'personal_requisition_ficha_entries.id', '=', 'employee_ficha_profiles.personal_requisition_ficha_entry_id')
                ->select(
                    'personal_requisition_ficha_entries.id as entry_id',
                    'personal_requisition_ficha_entries.personal_requisition_id',
                    'employee_ficha_profiles.first_surname',
                    'employee_ficha_profiles.second_surname',
                    'employee_ficha_profiles.first_name',
                    'employee_ficha_profiles.second_name'
                )
                ->get();

            foreach ($entries as $row) {
                DB::table('personal_requisition_ficha_entries')
                    ->where('id', $row->entry_id)
                    ->update([
                        'first_surname' => $row->first_surname,
                        'second_surname' => $row->second_surname,
                        'first_name' => $row->first_name,
                        'second_name' => $row->second_name,
                    ]);

                if ($row->personal_requisition_id) {
                    DB::table('personal_requisitions')
                        ->where('id', $row->personal_requisition_id)
                        ->update([
                            'hired_first_surname' => $row->first_surname,
                            'hired_second_surname' => $row->second_surname,
                            'hired_first_name' => $row->first_name,
                            'hired_second_name' => $row->second_name,
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('personal_requisitions')) {
            Schema::table('personal_requisitions', function (Blueprint $table): void {
                $columns = ['hired_first_surname', 'hired_second_surname', 'hired_first_name', 'hired_second_name'];
                $existing = array_filter($columns, fn ($c) => Schema::hasColumn('personal_requisitions', $c));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }

        if (Schema::hasTable('personal_requisition_ficha_entries')) {
            Schema::table('personal_requisition_ficha_entries', function (Blueprint $table): void {
                $columns = ['first_surname', 'second_surname', 'first_name', 'second_name'];
                $existing = array_filter($columns, fn ($c) => Schema::hasColumn('personal_requisition_ficha_entries', $c));
                if ($existing !== []) {
                    $table->dropColumn($existing);
                }
            });
        }
    }
};
