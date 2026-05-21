<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_listener_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 64)->unique();
            $table->foreignId('channel_id')
                ->nullable()
                ->constrained('radio_channels', 'id', 'fk_radio_listener_sessions_channel')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users', 'id', 'fk_radio_listener_sessions_user')
                ->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('ua_hash', 64)->nullable();
            $table->string('country', 4)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('bytes_streamed')->default(0);
            $table->unsignedInteger('tracks_played')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['channel_id', 'status', 'last_heartbeat_at'], 'idx_radio_listener_sessions_active');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_listener_sessions');
    }
};
