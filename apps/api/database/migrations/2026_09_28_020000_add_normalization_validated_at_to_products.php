<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->timestamp('normalization_validated_at')->nullable()->after('normalized_at');
        });

        DB::table('products')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('product_normalization_decisions')
                    ->whereColumn('product_normalization_decisions.product_id', 'products.id')
                    ->where('product_normalization_decisions.decision_source', 'manual');
            })
            ->update(['normalization_validated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('normalization_validated_at');
        });
    }
};
