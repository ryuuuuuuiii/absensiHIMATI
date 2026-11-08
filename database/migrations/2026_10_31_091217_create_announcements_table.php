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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->integer('admin_id')->nullable();
            $table->foreign('admin_id')->references('id')->on('admin')->onDelete('set null');
            $table->string('judul', 200);
            $table->text('konten');
            $table->string('tipe', 50)->default('info'); // info, penting, mendesak
            $table->string('target_type', 50)->default('all'); // all, departemen, personal
            $table->string('target_departemen', 100)->nullable();
            $table->integer('target_user_id')->nullable();
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_banner')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('target_type');
            $table->index('target_departemen');
            $table->index('target_user_id');
            $table->index('is_active');
            $table->index('is_banner');
            $table->index('pinned');
            $table->index('created_at');
        });

        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('announcement_id');
            $table->foreign('announcement_id')->references('id')->on('announcements')->onDelete('cascade');
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('is_dismissed')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
            $table->index('user_id');
            $table->index('is_dismissed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
