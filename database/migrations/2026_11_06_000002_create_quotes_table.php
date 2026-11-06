<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insured_id')->constrained()->restrictOnDelete();
            $table->char('destination_code', 2);
            $table->string('destination_name');
            $table->string('destination_region', 40);
            $table->string('destination_subregion', 80)->nullable();
            $table->string('pricing_region', 40);
            $table->string('destination_flag', 16)->nullable();
            $table->date('departure_date');
            $table->date('return_date');
            $table->unsignedSmallInteger('trip_days');
            $table->unsignedInteger('daily_rate_cents');
            $table->unsignedInteger('base_amount_cents');
            $table->unsignedTinyInteger('surcharge_percent');
            $table->unsignedInteger('surcharge_amount_cents');
            $table->unsignedInteger('total_amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('status', 20);
            $table->timestamp('contracted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('departure_date');
            $table->index('destination_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
