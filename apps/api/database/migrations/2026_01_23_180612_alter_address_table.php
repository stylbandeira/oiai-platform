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
        Schema::table('address', function (Blueprint $table) {
            $table->dropColumn('complement');
            $table->dropColumn('latitude');
            $table->dropColumn('longitude');
        });
        Schema::table('address', function (Blueprint $table) {
            $table->string('complement')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('address', function (Blueprint $table) {
            $table->string('complement');
            $table->string('latitude');
            $table->string('longitude');
        });
    }
};
