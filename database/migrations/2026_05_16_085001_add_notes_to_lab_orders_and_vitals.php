<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lab_orders', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('status');
        });

        Schema::table('vitals', function (Blueprint $table): void {
            $table->text('lab_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table): void {
            $table->dropColumn('notes');
        });

        Schema::table('vitals', function (Blueprint $table): void {
            $table->dropColumn('lab_notes');
        });
    }
};
