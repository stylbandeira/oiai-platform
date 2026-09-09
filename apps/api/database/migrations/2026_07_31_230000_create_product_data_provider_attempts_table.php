<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_data_provider_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('status', 20);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('message')->nullable();
            $table->unsignedInteger('attempts')->default(1);
            $table->timestamp('last_attempt_at');
            $table->timestamps();
            $table->unique(['product_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_data_provider_attempts');
    }
};
