<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->text('endpoint');
                $table->char('endpoint_hash', 64)->unique();
                $table->string('public_key', 255);
                $table->string('auth_token', 255);
                $table->string('content_encoding', 30)->default('aes128gcm');
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index('user_id', 'idx_push_subscriptions_user_id');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};

