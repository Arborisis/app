<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listening_point_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listening_point_id')->constrained()->cascadeOnDelete();

            $table->string('period_type', 20);
            $table->string('period_key', 20);

            $table->unsignedInteger('recordings_count')->default(0);
            $table->unsignedInteger('species_count')->default(0);
            $table->jsonb('species_list')->nullable();
            $table->jsonb('tags_distribution')->nullable();
            $table->decimal('avg_duration_seconds', 8, 2)->nullable();
            $table->decimal('avg_loudness_lufs', 7, 2)->nullable();
            $table->decimal('avg_spectral_centroid', 10, 2)->nullable();

            $table->decimal('biodiversity_score', 6, 3)->nullable();
            $table->decimal('acoustic_activity_score', 6, 3)->nullable();
            $table->jsonb('acoustic_profile')->nullable();

            $table->timestamps();

            $table->unique(['listening_point_id', 'period_type', 'period_key']);
            $table->index(['listening_point_id', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listening_point_snapshots');
    }
};
