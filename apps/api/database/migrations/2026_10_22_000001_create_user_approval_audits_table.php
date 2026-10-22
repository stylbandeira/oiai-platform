<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_approval_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['target_user_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_approval_audits');
    }
};
