<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->string('test_name');
            $table->text('notes')->nullable();
            $table->text('result')->nullable();
            $table->foreignUuid('result_recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('result_recorded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_items');
    }
};
