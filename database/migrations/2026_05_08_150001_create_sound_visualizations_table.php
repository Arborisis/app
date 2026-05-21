<?php

use App\Enums\FrequencyScale;
use App\Enums\SpectrogramType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_visualizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_analysis_id')
                ->constrained('sound_analyses')
                ->cascadeOnDelete();
            $table->string('type', 30); // SpectrogramType
            $table->string('scale', 20)->default(FrequencyScale::LINEAR->value); // FrequencyScale
            $table->string('file_path');
            $table->string('disk', 30)->default('audio');
            $table->jsonb('parameters_json')->nullable();
            $table->timestamps();

            $table->index(['sound_analysis_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_visualizations');
    }
};
