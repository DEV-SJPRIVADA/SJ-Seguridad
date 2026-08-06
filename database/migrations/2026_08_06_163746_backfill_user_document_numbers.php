<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->select(['id'])
            ->whereNull('document_number')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['document_number' => 'LEGACY-'.$user->id]);
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('document_number', 'like', 'LEGACY-%')
            ->update(['document_number' => null]);
    }
};
