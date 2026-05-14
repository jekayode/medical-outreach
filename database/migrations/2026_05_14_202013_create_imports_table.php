<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outreach_id')->constrained('outreaches')->cascadeOnDelete();
            $table->foreignUuid('imported_by_user_id')->constrained('users');
            $table->string('filename');
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('successful_rows');
            $table->unsignedInteger('failed_rows');
            $table->json('errors')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
