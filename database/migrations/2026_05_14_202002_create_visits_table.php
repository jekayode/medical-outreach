<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('beneficiary_id')->constrained('beneficiaries')->cascadeOnDelete();
            $table->foreignUuid('outreach_id')->constrained('outreaches')->cascadeOnDelete();
            $table->string('check_in_code')->unique();
            $table->timestamp('checked_in_at');
            $table->foreignUuid('checked_in_by_user_id')->constrained('users');
            $table->string('current_stage');
            $table->string('status');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['beneficiary_id', 'outreach_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
