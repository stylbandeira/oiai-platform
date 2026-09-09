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
        Schema::table('event', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('event', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->nullable();
            $table->string('target_type')->default('user')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('target_type');
        });
    }
};
