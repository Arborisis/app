<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sound_analyses', function (Blueprint $table) {
            $table->string('original_r2_key')->nullable()->after('sound_id');
            $table->timestamp('queued_at')->nullable()->after('original_r2_key');
            $table->timestamp('started_at')->nullable()->after('queued_at');
            $table->timestamp('completed_at')->nullable()->after('started_at');
            $table->timestamp('failed_at')->nullable()->after('completed_at');
            $table->unsignedTinyInteger('attempts')->default(0)->after('failed_at');
            $table->text('error_message')->nullable()->after('failed_reason');

            $table->decimal('duration_seconds', 8, 2)->nullable()->after('error_message');
            $table->unsignedInteger('sample_rate')->nullable()->after('duration_seconds');
            $table->unsignedTinyInteger('channels')->nullable()->after('sample_rate');
            $table->unsignedInteger('bitrate')->nullable()->after('channels');
            $table->string('format', 10)->nullable()->after('bitrate');

            $table->decimal('loudness_lufs', 6, 2)->nullable()->after('format');
            $table->decimal('peak_db', 6, 2)->nullable()->after('loudness_lufs');
            $table->decimal('rms_db', 6, 2)->nullable()->after('peak_db');
            $table->decimal('noise_floor_db', 6, 2)->nullable()->after('rms_db');
            $table->decimal('spectral_centroid', 10, 2)->nullable()->after('noise_floor_db');
            $table->decimal('spectral_rolloff', 10, 2)->nullable()->after('spectral_centroid');
            $table->decimal('zero_crossing_rate', 8, 4)->nullable()->after('spectral_rolloff');

            $table->string('waveform_r2_key')->nullable()->after('zero_crossing_rate');
            $table->string('spectrogram_r2_key')->nullable()->after('waveform_r2_key');
            $table->string('features_r2_key')->nullable()->after('spectrogram_r2_key');
            $table->string('birdnet_r2_key')->nullable()->after('features_r2_key');
            $table->string('summary_r2_key')->nullable()->after('birdnet_r2_key');
            $table->string('preview_r2_key')->nullable()->after('summary_r2_key');

            $table->string('quality_label', 20)->nullable()->after('preview_r2_key');
            $table->jsonb('quality_json')->nullable()->after('quality_label');
        });
    }

    public function down(): void
    {
        Schema::table('sound_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'original_r2_key',
                'queued_at',
                'started_at',
                'completed_at',
                'failed_at',
                'attempts',
                'error_message',
                'duration_seconds',
                'sample_rate',
                'channels',
                'bitrate',
                'format',
                'loudness_lufs',
                'peak_db',
                'rms_db',
                'noise_floor_db',
                'spectral_centroid',
                'spectral_rolloff',
                'zero_crossing_rate',
                'waveform_r2_key',
                'spectrogram_r2_key',
                'features_r2_key',
                'birdnet_r2_key',
                'summary_r2_key',
                'preview_r2_key',
                'quality_label',
                'quality_json',
            ]);
        });
    }
};
