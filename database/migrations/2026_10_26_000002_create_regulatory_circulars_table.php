<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('regulatory_circulars', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['CBIC_CUSTOMS', 'DGFT', 'MINISTRY_COMMERCE']);
            $table->string('notification_number', 100);
            $table->string('title', 500);
            $table->string('gazette_url', 1000)->nullable();
            $table->string('local_pdf_path', 500)->nullable();
            $table->date('issue_date')->index();
            $table->date('effective_date');
            $table->enum('parse_status', ['PENDING', 'PROCESSING', 'PARSED', 'FAILED', 'MANUAL_REVIEW'])
                  ->default('PENDING')
                  ->index();
            $table->json('raw_payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['source', 'notification_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulatory_circulars');
    }
};
