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
        if (! Schema::hasTable('stored_uploads')) {
            Schema::create('stored_uploads', function (Blueprint $table) {
                $table->id();
                $table->string('folder', 50);
                $table->string('filename', 255);
                $table->string('mime_type', 100);
                $table->unsignedInteger('file_size');
                $table->binary('payload', 16777215); // MEDIUMBLOB / LONGBLOB
                $table->dateTime('created_at')->useCurrent();

                $table->unique(['folder', 'filename'], 'uk_folder_filename');
                $table->index('filename', 'idx_filename');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stored_uploads');
    }
};
