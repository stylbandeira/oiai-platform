<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_normalization_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->text('raw_name');
            $table->string('normalized_raw_name', 255);
            $table->json('selected_values');
            $table->string('decision_source', 32);
            $table->unsignedInteger('algorithm_version');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['normalized_raw_name', 'algorithm_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_normalization_decisions');
    }
};
