<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Executa SQL direto - SEM DBAL necessário!
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY quantity FLOAT DEFAULT 1');
        } else {
            // Para outros bancos, adapte:
            DB::statement('ALTER TABLE products ALTER COLUMN quantity TYPE FLOAT');
            DB::statement('ALTER TABLE products ALTER COLUMN quantity SET DEFAULT 1');
        }
    }

    public function down()
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY quantity INT DEFAULT 1');
        } else {
            DB::statement('ALTER TABLE products ALTER COLUMN quantity TYPE INTEGER');
            DB::statement('ALTER TABLE products ALTER COLUMN quantity SET DEFAULT 1');
        }
    }
};
