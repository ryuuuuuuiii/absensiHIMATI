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
        Schema::table('member_badges', function (Blueprint $table) {
            if (! Schema::hasColumn('member_badges', 'attendance_summary')) {
                $table->json('attendance_summary')->nullable()->after('exp_earned');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_badges', function (Blueprint $table) {
            if (Schema::hasColumn('member_badges', 'attendance_summary')) {
                $table->dropColumn('attendance_summary');
            }
        });
    }
};
