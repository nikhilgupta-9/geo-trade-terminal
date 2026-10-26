<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quarantined_tariff_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_id')->constrained('regulatory_circulars')->cascadeOnDelete();
            $table->string('raw_hs_code', 50)->nullable();
            $table->foreignId('matched_hs_code_id')->nullable()->constrained('hs_codes')->nullOnDelete();
            $table->enum('duty_type', ['BCD', 'AIDC', 'ADD', 'SAFEGUARD', 'CESS'])->default('BCD');
            $table->decimal('extracted_old_rate', 5, 2)->nullable();
            $table->decimal('extracted_new_rate', 5, 2)->nullable();
            $table->decimal('confidence_score', 4, 2)->default(0.00);
            $table->enum('quarantine_reason', [
                'AMBIGUOUS_CODE',
                'RATE_ANOMALY',
                'MISSING_BASELINE',
                'REGEX_UNCERTAINTY',
                'PARSE_EXCEPTION'
            ])->default('REGEX_UNCERTAINTY');
            $table->text('raw_snippet')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['PENDING_REVIEW', 'APPROVED', 'REJECTED'])->default('PENDING_REVIEW')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarantined_tariff_revisions');
    }
};
