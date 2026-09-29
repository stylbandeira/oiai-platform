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
        $unities = [
            'un' => [
                'name' => 'unidade',
                'dimension' => 'unit',
                'convertion_factor' => 1,
                'base_abbreviation' => 'un',
            ],
            'g' => [
                'name' => 'grama',
                'dimension' => 'mass',
                'convertion_factor' => 1,
                'base_abbreviation' => 'g',
            ],
            'kg' => [
                'name' => 'quilograma',
                'dimension' => 'mass',
                'convertion_factor' => 1000,
                'base_abbreviation' => 'g',
            ],
            'ml' => [
                'name' => 'mililitro',
                'dimension' => 'volume',
                'convertion_factor' => 1,
                'base_abbreviation' => 'ml',
            ],
            'l' => [
                'name' => 'litro',
                'dimension' => 'volume',
                'convertion_factor' => 1000,
                'base_abbreviation' => 'ml',
            ],
        ];

        DB::transaction(function () use ($unities, $now) {
            foreach ($unities as $abbreviation => $unity) {
                $values = [
                    'name' => $unity['name'],
                    'dimension' => $unity['dimension'],
                    'convertion_factor' => $unity['convertion_factor'],
                    'deleted_at' => null,
                    'updated_at' => $now,
                ];

                $existingUnity = DB::table('unities')
                    ->where('abbreviation', $abbreviation);

                if ($existingUnity->exists()) {
                    $existingUnity->update($values);
                } else {
                    DB::table('unities')->insert([
                        'abbreviation' => $abbreviation,
                        'created_at' => $now,
                        ...$values,
                    ]);
                }
            }

            $idsByAbbreviation = DB::table('unities')
                ->whereIn('abbreviation', array_keys($unities))
                ->pluck('id', 'abbreviation');

            foreach ($unities as $abbreviation => $unity) {
                DB::table('unities')
                    ->where('abbreviation', $abbreviation)
                    ->update([
                        'base_unity_id' => $idsByAbbreviation[$unity['base_abbreviation']],
                        'updated_at' => $now,
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Os registros podem já existir ou estar em uso por produtos.
        // O rollback preserva os dados para não remover unidades do usuário.
    }
};
