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
            $table->boolean('host_personalities_enabled')->default(false)->after('listener_ttl_seconds');
            $table->boolean('host_continuity_enabled')->default(false)->after('host_personalities_enabled');
            $table->boolean('production_presets_enabled')->default(false)->after('host_continuity_enabled');
            $table->boolean('player_visualizer_enabled')->default(false)->after('production_presets_enabled');
            $table->boolean('player_realtime_enabled')->default(false)->after('player_visualizer_enabled');
            $table->boolean('monitoring_enabled')->default(false)->after('player_realtime_enabled');
            $table->boolean('multichannel_enabled')->default(false)->after('monitoring_enabled');
            $table->boolean('discord_embeds_enabled')->default(false)->after('multichannel_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('radio_settings', function (Blueprint $table) {
            $table->dropColumn([
                'host_personalities_enabled',
                'host_continuity_enabled',
                'production_presets_enabled',
                'player_visualizer_enabled',
                'player_realtime_enabled',
                'monitoring_enabled',
                'multichannel_enabled',
                'discord_embeds_enabled',
            ]);
        });
    }
};
