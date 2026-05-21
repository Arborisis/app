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
            $table->boolean('podcast_enabled')->default(false)->after('discord_auto_join');
            $table->unsignedSmallInteger('podcast_interval_tracks')->default(15)->after('podcast_enabled');
            $table->unsignedSmallInteger('podcast_min_duration')->default(120)->after('podcast_interval_tracks');
            $table->unsignedSmallInteger('podcast_max_duration')->default(240)->after('podcast_min_duration');
            $table->string('podcast_voice_id')->nullable()->after('podcast_max_duration');
        });
    }

    public function down(): void
    {
        Schema::table('radio_settings', function (Blueprint $table) {
            $table->dropColumn([
                'podcast_enabled',
                'podcast_interval_tracks',
                'podcast_min_duration',
                'podcast_max_duration',
                'podcast_voice_id',
            ]);
        });
    }
};
