<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('normalized_name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('product_type_id')
                ->nullable()
                ->after('category_id')
                ->constrained('product_types')
                ->nullOnDelete();
            $table->unsignedBigInteger('brand_id')->nullable()->after('product_type_id')->index();
            $table->unsignedBigInteger('variant_id')->nullable()->after('brand_id')->index();
        });

        Schema::create('shopping_list_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('list_id')->constrained('list')->cascadeOnDelete();
            $table->foreignId('product_type_id')->constrained('product_types');
            $table->decimal('desired_quantity', 12, 3);
            $table->foreignId('desired_unit_id')->constrained('unities');
            $table->unsignedBigInteger('brand_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->foreignId('specific_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('company_products', function (Blueprint $table): void {
            $table->decimal('current_price', 12, 2)->nullable()->after('average_price');
            $table->decimal('price_per_base_unit', 16, 6)->nullable()->after('current_price');
        });

        DB::table('company_products')->update([
            'current_price' => DB::raw('average_price'),
        ]);

        DB::table('company_products')
            ->join('products', 'products.id', '=', 'company_products.product_id')
            ->join('unities', 'unities.id', '=', 'products.unit_id')
            ->whereNotNull('company_products.current_price')
            ->where('products.quantity', '>', 0)
            ->where('unities.convertion_factor', '>', 0)
            ->update([
                'company_products.price_per_base_unit' => DB::raw(
                    'company_products.current_price / (products.quantity * unities.convertion_factor)'
                ),
            ]);
    }

    public function down(): void
    {
        Schema::table('company_products', function (Blueprint $table): void {
            $table->dropColumn(['current_price', 'price_per_base_unit']);
        });
        Schema::dropIfExists('shopping_list_requirements');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['brand_id', 'variant_id']);
            $table->dropConstrainedForeignId('product_type_id');
        });
        Schema::dropIfExists('product_types');
    }
};
