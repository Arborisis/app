<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_worker_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_worker_id')->constrained('audio_workers')->onDelete('cascade');
            $table->foreignId('sound_analysis_id')->constrained('sound_analyses')->onDelete('cascade');
            $table->enum('status', ['assigned', 'processing', 'completed', 'failed', 'timeout'])->default('assigned');
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->unique(['audio_worker_id', 'sound_analysis_id']);
            $table->index(['status', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_worker_assignments');
    }
};