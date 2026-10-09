<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductionInitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $fallbackCategory = ProductCategory::withTrashed()->firstOrNew([
                'name' => 'Sem categoria',
            ]);
            $fallbackCategory->description ??= 'Produtos sem categoria definida.';

            if ($fallbackCategory->trashed()) {
                $fallbackCategory->restore();
            }

            $fallbackCategory->save();

            Product::withTrashed()
                ->where(function ($query) {
                    $query->whereNull('category_id')
                        ->orWhereDoesntHave('category');
                })
                ->update(['category_id' => $fallbackCategory->getKey()]);

            User::updateOrCreate(
                ['email' => 'stylbandeira@gmail.com'],
                [
                    'name' => 'Administrador',
                    'type' => User::TYPE_ADMIN,
                    'password' => Hash::make(env('INITIAL_ADMIN_PASSWORD')),
                    'status' => 'active',
                ]
            );
        });
    }
}
