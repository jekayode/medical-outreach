<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outreaches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('location');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('code_prefix');
            $table->string('status');
            $table->unsignedInteger('next_check_in_sequence')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreaches');
    }
};
