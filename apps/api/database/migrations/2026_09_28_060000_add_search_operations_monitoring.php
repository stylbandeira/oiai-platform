<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('search_document_version')->default(0)->index();
            $table->timestamp('search_indexed_at')->nullable()->index();
        });

        Schema::table('search_logs', function (Blueprint $table): void {
            $table->string('search_engine', 32)->nullable()->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('fallback_used')->default(false);
        });

        Schema::create('product_pipeline_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('operation', 32);
            $table->string('status', 16);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['operation', 'status', 'created_at'], 'pipeline_operation_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pipeline_runs');

        Schema::table('search_logs', function (Blueprint $table): void {
            $table->dropColumn(['search_engine', 'duration_ms', 'fallback_used']);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['search_document_version', 'search_indexed_at']);
        });
    }
};
