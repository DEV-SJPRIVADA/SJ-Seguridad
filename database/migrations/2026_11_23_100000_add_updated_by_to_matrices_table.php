<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Autor de la última edición de la definición de una matriz.
 * `owner_user_id` sigue siendo el creador; esta columna permite mostrar
 * «Creador» y «Última edición» por separado en «Mis matrices».
 */
return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('matrices', function (Blueprint $table) use ($isSqlite): void {
            if ($isSqlite) {
                // SQLite no permite agregar FKs a una tabla existente.
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
            } else {
                $table->foreignId('updated_by_user_id')
                    ->nullable()
                    ->after('owner_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::table('matrices', function (Blueprint $table) use ($isSqlite): void {
            if (! $isSqlite) {
                $table->dropForeign(['updated_by_user_id']);
            }

            $table->dropColumn('updated_by_user_id');
        });
    }
};
