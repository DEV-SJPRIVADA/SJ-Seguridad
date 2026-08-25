<?php

namespace Database\Seeders;

use App\Models\WordDocumentType;
use Illuminate\Database\Seeder;

class WordDocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        WordDocumentType::query()->firstOrCreate(
            ['code' => 'desvinculacion'],
            [
                'name' => 'Desvinculacion',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}
