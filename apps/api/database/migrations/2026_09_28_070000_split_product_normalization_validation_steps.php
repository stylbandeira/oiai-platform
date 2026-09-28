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
            $table->timestamp('name_normalization_validated_at')->nullable()->after('normalization_validated_at');
            $table->timestamp('quantity_normalization_validated_at')->nullable()->after('name_normalization_validated_at');
        });

        DB::table('products')->update(['normalization_validated_at' => null]);

        DB::table('product_normalization_decisions')
            ->where('decision_source', 'manual')
            ->orderBy('id')
            ->chunkById(500, function ($decisions): void {
                foreach ($decisions as $decision) {
                    $values = json_decode((string) $decision->selected_values, true);
                    if (! is_array($values)) {
                        continue;
                    }

                    $updates = [];
                    if (isset($values['normalized_name'])) {
                        $updates['name_normalization_validated_at'] = $decision->created_at;
                    }
                    if (isset($values['normalized_quantity'], $values['quantity_dimension'])) {
                        $updates['quantity_normalization_validated_at'] = $decision->created_at;
                    }
                    if ($updates !== []) {
                        DB::table('products')->where('id', $decision->product_id)->update($updates);
                    }
                }
            });

        DB::table('products')->orderBy('id')->chunkById(500, function ($products): void {
            foreach ($products as $product) {
                $requiresQuantity = preg_match('/\d/u', (string) ($product->raw_name ?: $product->name)) === 1;
                $complete = $product->name_normalization_validated_at !== null
                    && (! $requiresQuantity || $product->quantity_normalization_validated_at !== null);

                if ($complete) {
                    DB::table('products')->where('id', $product->id)->update([
                        'normalization_validated_at' => $product->name_normalization_validated_at,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'name_normalization_validated_at',
                'quantity_normalization_validated_at',
            ]);
        });
    }
};
