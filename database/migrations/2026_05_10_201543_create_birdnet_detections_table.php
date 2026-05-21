<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('birdnet_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_analysis_id')->constrained('sound_analyses')->cascadeOnDelete();
            $table->foreignId('sound_id')->constrained('sounds')->cascadeOnDelete();
            $table->string('scientific_name');
            $table->string('common_name');
            $table->decimal('confidence', 4, 3);
            $table->decimal('start_time', 8, 2);
            $table->decimal('end_time', 8, 2);
            $table->decimal('frequency_min', 10, 2)->nullable();
            $table->decimal('frequency_max', 10, 2)->nullable();
            $table->string('source', 30)->default('birdnet');
            $table->timestamps();

            $table->index(['sound_id', 'confidence']);
            $table->index('scientific_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('birdnet_detections');
    }
};
