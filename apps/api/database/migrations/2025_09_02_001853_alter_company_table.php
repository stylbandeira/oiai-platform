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
            $table->dropColumn('address_id');
        });
        Schema::table('company', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->default(null);
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
            $table->dropColumn('address_id');
        });
        Schema::table('company', function (Blueprint $table) {
            $table->foreignId('address_id');
        });
    }
};
