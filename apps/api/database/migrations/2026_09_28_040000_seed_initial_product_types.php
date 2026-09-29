<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $now = now();
        foreach ([
            'arroz' => 'Arroz',
            'leite' => 'Leite',
            'achocolatado' => 'Achocolatado',
            'sabao em po' => 'Sabão em pó',
        ] as $normalizedName => $name) {
            DB::table('product_types')->updateOrInsert(
                ['normalized_name' => $normalizedName],
                ['name' => $name, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        // Preserve types because products and requirements may reference them.
    }
};
