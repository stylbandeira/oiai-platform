<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_normalization_decisions', 'confidence')) {
            Schema::table('product_normalization_decisions', function (Blueprint $table): void {
                $table->decimal('confidence', 5, 4)->default(0)->after('algorithm_version');
            });
        }

        if (! Schema::hasColumn('product_normalization_decisions', 'confirmation_count')) {
            Schema::table('product_normalization_decisions', function (Blueprint $table): void {
                $table->unsignedInteger('confirmation_count')->default(1)->after('confidence');
            });
        }
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['confidence', 'confirmation_count'],
            fn (string $column): bool => Schema::hasColumn('product_normalization_decisions', $column),
        ));

        if ($columns !== []) {
            Schema::table('product_normalization_decisions', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
