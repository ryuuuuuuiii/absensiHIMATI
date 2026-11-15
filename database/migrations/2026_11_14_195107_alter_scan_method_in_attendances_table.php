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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('scan_method', 50)->default('qr_scan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back if needed, or keep string
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('scan_method', 30)->default('qr_scan')->change();
        });
    }
};
