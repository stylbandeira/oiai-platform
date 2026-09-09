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
        Schema::table('user_added_products', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->dropColumn('product_id');
            $table->dropColumn('price');
        });

        Schema::table('user_added_products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->double('price')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_added_products', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->dropColumn('product_id');
            $table->dropColumn('price');
        });

        Schema::table('user_added_products', function (Blueprint $table) {
            $table->foreignId('company_id');
            $table->foreignId('product_id');
            $table->double('price');
        });
    }
};
