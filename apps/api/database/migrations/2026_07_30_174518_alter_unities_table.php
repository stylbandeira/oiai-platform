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
        Schema::table('unities', function (Blueprint $table) {
            $table->string('dimension')->nullable()->after('name');
            $table->integer('convertion_factor')->nullable()->after('dimension');
            $table->foreignId('base_unity_id')->nullable()->after('convertion_factor')->constrained('unities');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('dimension');
            $table->dropColumn('convertion_factor');
            $table->dropForeign(['base_unity_id']);
        });
    }
};
