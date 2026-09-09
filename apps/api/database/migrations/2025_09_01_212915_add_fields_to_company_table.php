<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->string('site')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('inactive');
            $table->string('phone')->nullable();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company', function (Blueprint $table) {
            $table->dropColumn('site');
            $table->dropColumn('email');
            $table->dropColumn('status');
            $table->dropColumn('phone');
            $table->dropColumn('description');
        });
    }
};
