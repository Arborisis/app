<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radio_settings', function (Blueprint $table) {
            $table->string('engine', 30)->default('icecast')->after('station_name');
            $table->string('public_stream_url')->nullable()->after('engine');
            $table->string('icecast_base_url')->nullable()->after('public_stream_url');
            $table->string('icecast_mount')->default('/arborisis.mp3')->after('icecast_base_url');
            $table->unsignedInteger('crossfade_seconds')->default(4)->after('gap_ms');
            $table->boolean('dj_enabled')->default(true)->after('loop_enabled');
            $table->unsignedSmallInteger('dj_announcement_frequency')->default(3)->after('dj_enabled');
            $table->string('dj_voice_provider', 30)->default('elevenlabs')->after('dj_announcement_frequency');
            $table->string('dj_voice_id')->nullable()->after('dj_voice_provider');
            $table->string('discord_voice_channel_id')->nullable()->after('max_listeners_display');
            $table->boolean('discord_auto_join')->default(true)->after('discord_voice_channel_id');
        });

        Schema::create('radio_dj_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_id')
                ->nullable()
                ->constrained('sounds', 'id', 'fk_radio_dj_announcements_sound')
                ->nullOnDelete();
            $table->string('voice_provider', 30)->default('elevenlabs');
            $table->string('voice_id')->nullable();
            $table->text('text');
            $table->string('disk', 30)->default('audio');
            $table->string('path');
            $table->string('mime_type')->default('audio/mpeg');
            $table->unsignedInteger('duration')->nullable();
            $table->string('text_hash', 64)->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['voice_provider', 'voice_id', 'text_hash'], 'radio_dj_voice_text_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_dj_announcements');

        Schema::table('radio_settings', function (Blueprint $table) {
            $table->dropColumn([
                'engine',
                'public_stream_url',
                'icecast_base_url',
                'icecast_mount',
                'crossfade_seconds',
                'dj_enabled',
                'dj_announcement_frequency',
                'dj_voice_provider',
                'dj_voice_id',
                'discord_voice_channel_id',
                'discord_auto_join',
            ]);
        });
    }
};
