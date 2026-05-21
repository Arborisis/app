<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sound_analyses', function (Blueprint $table) {
            $table->jsonb('birdnet_raw_json')->nullable()->after('preview_r2_key');
            $table->unsignedInteger('acoustic_event_count')->nullable()->after('birdnet_raw_json');
            $table->decimal('acoustic_diversity_index', 8, 4)->nullable()->after('acoustic_event_count');

            $table->index('acoustic_event_count');
            $table->index('acoustic_diversity_index');
        });
    }

    public function down(): void
    {
        Schema::table('sound_analyses', function (Blueprint $table) {
            $table->dropIndex(['acoustic_diversity_index']);
            $table->dropIndex(['acoustic_event_count']);
            $table->dropColumn(['birdnet_raw_json', 'acoustic_event_count', 'acoustic_diversity_index']);
        });
    }
};
