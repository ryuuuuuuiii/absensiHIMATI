<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom exp ke tabel users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'exp')) {
                $table->unsignedInteger('exp')->default(0)->after('last_login_at');
            }
        });

        // 2. Tambah kolom exp_awarded ke tabel attendances
        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'exp_awarded')) {
                $table->boolean('exp_awarded')->default(false)->after('sp_ignored');
            }
        });

        // 3. Tambah kolom completed_at ke tabel events & perlebar status
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('status');
            }
        });

        try {
            DB::statement("ALTER TABLE events MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active'");
        } catch (Throwable $e) {
            // fallback if already modified or running in sqlite/testing
        }

        // 4. Buat tabel member_badges
        if (! Schema::hasTable('member_badges')) {
            Schema::create('member_badges', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->integer('event_id');
                $table->string('event_name', 200);
                $table->string('jabatan', 100);
                $table->string('divisi', 100)->nullable();
                $table->string('badge_type', 50)->default('event_completion');
                $table->integer('exp_earned')->default(0);
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->foreign('event_id')
                    ->references('id')
                    ->on('events')
                    ->onDelete('cascade');

                $table->unique(['user_id', 'event_id'], 'uk_member_badge_user_event');
            });
        }

        // 5. Backfill EXP untuk kehadiran 'hadir' yang sudah ada di database (50 EXP per kehadiran)
        try {
            $hadirAttendances = DB::table('attendances')
                ->where('status', 'hadir')
                ->where('exp_awarded', false)
                ->get();

            $expByUser = [];
            foreach ($hadirAttendances as $att) {
                $expByUser[$att->user_id] = ($expByUser[$att->user_id] ?? 0) + 50;
            }

            foreach ($expByUser as $userId => $totalExp) {
                DB::table('users')->where('id', $userId)->increment('exp', $totalExp);
            }

            DB::table('attendances')
                ->where('status', 'hadir')
                ->update(['exp_awarded' => true]);
        } catch (Throwable $e) {
            // Ignore if in fresh testing environment
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_badges');

        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'exp_awarded')) {
                $table->dropColumn('exp_awarded');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'exp')) {
                $table->dropColumn('exp');
            }
        });
    }
};
