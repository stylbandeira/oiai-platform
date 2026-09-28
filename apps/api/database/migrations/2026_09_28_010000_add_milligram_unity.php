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

        $gramId = DB::table('unities')->where('abbreviation', 'g')->value('id');

        DB::table('unities')->updateOrInsert(
            ['abbreviation' => 'mg'],
            [
                'name' => 'miligrama',
                'dimension' => 'mass',
                'convertion_factor' => 0.001,
                'base_unity_id' => $gramId,
                'deleted_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        // Preserve the unit because products may already reference it.
    }
};
