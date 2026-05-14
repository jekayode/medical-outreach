<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselling_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visit_id')->unique()->constrained('visits')->cascadeOnDelete();
            $table->foreignUuid('counsellor_user_id')->constrained('users');
            $table->json('types');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counselling_sessions');
    }
};
