<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eye_exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('intervention_id')->unique()->constrained('interventions')->cascadeOnDelete();
            $table->foreignUuid('examined_by_user_id')->constrained('users');
            $table->string('visual_acuity_left')->nullable();
            $table->string('visual_acuity_right')->nullable();
            $table->text('findings')->nullable();
            $table->boolean('glasses_prescribed')->default(false);
            $table->text('glasses_prescription_details')->nullable();
            $table->boolean('drops_prescribed')->default(false);
            $table->boolean('referral_needed')->default(false);
            $table->text('referral_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eye_exams');
    }
};
