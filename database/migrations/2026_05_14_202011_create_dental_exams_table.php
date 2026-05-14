<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intervention_id')->unique()->constrained('interventions')->cascadeOnDelete();
            $table->foreignUuid('examined_by_user_id')->constrained('users');
            $table->text('findings');
            $table->text('treatment_performed')->nullable();
            $table->boolean('referral_needed')->default(false);
            $table->text('referral_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_exams');
    }
};
