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
        Schema::table('company_owners', function (Blueprint $table) {
            $table->string('status')->default('pending');
            $table->string('message')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_owners', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('message');
            $table->dropColumn('approved_at');
            $table->dropColumn('approved_by');
        });
    }
};
