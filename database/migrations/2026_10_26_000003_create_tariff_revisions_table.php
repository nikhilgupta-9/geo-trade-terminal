<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tariff_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained('regulatory_circulars')->cascadeOnDelete();
            $table->foreignId('hs_code_id')->constrained('hs_codes')->restrictOnDelete();
            $table->enum('duty_type', ['BCD', 'AIDC', 'ADD', 'SAFEGUARD', 'CESS'])->default('BCD');
            $table->decimal('old_rate', 5, 2);
            $table->decimal('new_rate', 5, 2);
            $table->decimal('delta', 5, 2)->storedAs('new_rate - old_rate');
            $table->enum('action', ['INCREASE', 'DECREASE', 'UNCHANGED', 'EXEMPTED'])->index();
            $table->text('condition_notes')->nullable();
            $table->date('effective_from')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariff_revisions');
    }
};
