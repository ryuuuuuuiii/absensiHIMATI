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
            if (! Schema::hasColumn('users', 'nim')) {
                $table->string('nim', 20)->nullable()->index()->after('jabatan');
            }
            if (! Schema::hasColumn('users', 'nrp')) {
                $table->string('nrp', 50)->nullable()->index()->after('nim');
            }
            if (! Schema::hasColumn('users', 'angkatan')) {
                $table->string('angkatan', 10)->nullable()->after('nrp');
            }
            if (! Schema::hasColumn('users', 'ttl')) {
                $table->string('ttl', 50)->nullable()->after('angkatan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['nim', 'nrp', 'angkatan', 'ttl'], fn ($col) => Schema::hasColumn('users', $col));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
