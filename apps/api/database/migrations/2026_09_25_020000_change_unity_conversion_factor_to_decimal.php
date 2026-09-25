<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unities', function (Blueprint $table): void {
            $table->decimal('convertion_factor', 12, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('unities', function (Blueprint $table): void {
            $table->integer('convertion_factor')->nullable()->change();
        });
    }
};
