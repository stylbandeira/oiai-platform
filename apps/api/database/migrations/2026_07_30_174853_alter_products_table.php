<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('normalized_quantity')->nullable()->after('name');
            $table->string('quantity_dimension')->nullable()->after('normalized_quantity');
            $table->string('quantity_source')->nullable()->after('quantity_dimension');
            $table->decimal('quantity_confidence', 3, 2)->nullable()->after('quantity_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('normalized_quantity');
            $table->dropColumn('quantity_dimension');
            $table->dropColumn('quantity_source');
            $table->dropColumn('quantity_confidence');
        });
    }
};
