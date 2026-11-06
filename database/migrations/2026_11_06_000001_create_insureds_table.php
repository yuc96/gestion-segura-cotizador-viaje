<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insureds', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('identification', 20);
            $table->string('email', 150);
            $table->date('birth_date');
            $table->timestamps();

            $table->index('identification');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insureds');
    }
};
