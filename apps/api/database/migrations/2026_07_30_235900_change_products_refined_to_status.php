<?php

use App\Enums\ProductRefinementStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('refinement_status')
                ->default(ProductRefinementStatus::Unrefined->value)
                ->after('refined');
        });

        DB::table('products')
            ->where('refined', true)
            ->update(['refinement_status' => ProductRefinementStatus::CosmosValidated->value]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('refined');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('refinement_status', 'refined');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('was_refined')->default(false)->after('refined');
        });

        DB::table('products')
            ->where('refined', '!=', ProductRefinementStatus::Unrefined->value)
            ->update(['was_refined' => true]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('refined');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('was_refined', 'refined');
        });
    }
};
