<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->text('residential_address');
            $table->text('existing_medical_conditions')->nullable();
            $table->string('medication_status')->nullable();
            $table->text('medication_list')->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->boolean('medical_consent')->default(false);
            $table->string('communication_preference')->nullable();
            $table->string('source');
            $table->timestamp('imported_at')->nullable();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
