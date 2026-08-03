<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{slug: string, name: string, sort_order: int}>
     */
    private const DEFAULT_PORTFOLIOS = [
        ['slug' => 'seg_fisica', 'name' => 'Seg. Fisica', 'sort_order' => 1],
        ['slug' => 'monitoreo', 'name' => 'Monitoreo', 'sort_order' => 2],
        ['slug' => 'ocasionales', 'name' => 'Ocasionales', 'sort_order' => 3],
        ['slug' => 'inactivos', 'name' => 'Inactivos', 'sort_order' => 4],
    ];

    public function up(): void
    {
        Schema::create('commercial_portfolios', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        foreach (self::DEFAULT_PORTFOLIOS as $portfolio) {
            DB::table('commercial_portfolios')->insert([
                'slug' => $portfolio['slug'],
                'name' => $portfolio['name'],
                'is_active' => true,
                'sort_order' => $portfolio['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_portfolios');
    }
};
