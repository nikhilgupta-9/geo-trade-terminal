<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_watchlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hs_code_id')->constrained('hs_codes')->cascadeOnDelete();
            $table->decimal('baseline_fob_inr', 15, 2)->nullable();
            $table->unsignedInteger('monthly_volume_units')->default(1);
            $table->boolean('notify_on_increase_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'hs_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_watchlists');
    }
};
