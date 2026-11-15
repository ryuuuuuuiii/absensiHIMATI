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
        if (Schema::hasColumn('meetings', 'kategori')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('kategori', 20)->default('rapat')->after('jenis_rapat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('meetings', 'kategori')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
