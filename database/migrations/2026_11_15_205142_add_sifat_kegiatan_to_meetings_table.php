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
        if (Schema::hasColumn('meetings', 'sifat_kegiatan')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('sifat_kegiatan', 20)->nullable()->after('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('meetings', 'sifat_kegiatan')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('sifat_kegiatan');
        });
    }
};
