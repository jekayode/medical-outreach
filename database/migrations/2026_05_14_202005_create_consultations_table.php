<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intervention_id')->unique()->constrained('interventions')->cascadeOnDelete();
            $table->foreignUuid('doctor_user_id')->constrained('users');
            $table->text('chief_complaint');
            $table->text('observations')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('next_action')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
