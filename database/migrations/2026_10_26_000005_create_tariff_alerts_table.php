<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tariff_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hs_code_id')->constrained('hs_codes')->cascadeOnDelete();
            $table->foreignId('revision_id')->constrained('tariff_revisions')->cascadeOnDelete();
            $table->decimal('old_rate', 5, 2);
            $table->decimal('new_rate', 5, 2);
            $table->decimal('delta', 5, 2);
            $table->decimal('estimated_monthly_impact_inr', 15, 2)->default(0.00);
            $table->enum('status', ['UNREAD', 'READ', 'DISMISSED'])->default('UNREAD')->index();
            $table->enum('channel', ['IN_APP', 'EMAIL', 'WEBHOOK'])->default('IN_APP');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_alerts');
    }
};
