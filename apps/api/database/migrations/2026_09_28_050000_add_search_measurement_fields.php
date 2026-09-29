<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('package_count')->nullable()->after('normalized_quantity');
            $table->boolean('normalization_conflict')->default(false)->after('package_count');
        });

        Schema::create('search_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('query', 500);
            $table->string('normalized_query', 500);
            $table->unsignedInteger('result_count')->default(0);
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['normalized_query', 'result_count'], 'search_logs_query_results_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['package_count', 'normalization_conflict']);
        });
    }
};
