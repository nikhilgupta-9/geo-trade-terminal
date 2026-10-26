<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hs_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->char('chapter', 2)->index();
            $table->text('description');
            $table->string('unit', 20)->default('KGS');
            $table->decimal('base_bcd_rate', 5, 2)->default(0.00)->comment('Basic Customs Duty %');
            $table->decimal('sws_rate', 5, 2)->default(10.00)->comment('Social Welfare Surcharge %');
            $table->decimal('igst_rate', 5, 2)->default(18.00)->comment('Integrated GST %');
            $table->decimal('aidc_rate', 5, 2)->default(0.00)->comment('AIDC %');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hs_codes');
    }
};
