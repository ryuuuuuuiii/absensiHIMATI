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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'sp_level')) {
                $table->unsignedTinyInteger('sp_level')->default(0)->after('is_bph');
            }
            if (! Schema::hasColumn('users', 'sp_base_level')) {
                $table->unsignedTinyInteger('sp_base_level')->default(0)->after('sp_level');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sp_base_level');
            }
            if (! Schema::hasColumn('users', 'sp_reason')) {
                $table->string('sp_reason', 255)->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('users', 'sp_issued_at')) {
                $table->timestamp('sp_issued_at')->nullable()->after('sp_reason');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'sp_ignored')) {
                $table->boolean('sp_ignored')->default(false)->after('scan_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'sp_ignored')) {
                $table->dropColumn('sp_ignored');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(
                ['sp_level', 'sp_base_level', 'is_active', 'sp_reason', 'sp_issued_at'],
                fn (string $col) => Schema::hasColumn('users', $col)
            );
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
