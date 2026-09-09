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
        Schema::create('completed_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->unique()->constrained('list')->cascadeOnDelete();
            $table->json('list_data');
            $table->string('version');
            $table->decimal('total_price', 12, 2);
            $table->dateTime('completed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completed_lists');
    }
};
