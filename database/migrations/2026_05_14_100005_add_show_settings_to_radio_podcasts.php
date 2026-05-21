<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radio_podcasts', function (Blueprint $table) {
            $table->string('host_personality_slug', 60)->nullable()->after('voice_id');
            $table->string('production_preset', 40)->nullable()->after('host_personality_slug');
            $table->foreignId('channel_id')
                ->nullable()
                ->after('production_preset')
                ->constrained('radio_channels', 'id', 'fk_radio_podcasts_channel')
                ->nullOnDelete();
            $table->string('idempotency_key', 80)->nullable()->after('channel_id');

            $table->unique('idempotency_key', 'radio_podcasts_idempotency_key_unique');
            $table->index('host_personality_slug');
            $table->index('production_preset');
        });
    }

    public function down(): void
    {
        Schema::table('radio_podcasts', function (Blueprint $table) {
            $table->dropForeign('fk_radio_podcasts_channel');
            $table->dropUnique('radio_podcasts_idempotency_key_unique');
            $table->dropIndex(['host_personality_slug']);
            $table->dropIndex(['production_preset']);
            $table->dropColumn([
                'host_personality_slug',
                'production_preset',
                'channel_id',
                'idempotency_key',
            ]);
        });
    }
};
