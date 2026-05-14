<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->string('drug_name');
            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration');
            $table->unsignedInteger('quantity');
            $table->string('availability')->nullable();
            $table->string('dispensed_status')->default('pending');
            $table->foreignUuid('dispensed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispensed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
