<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modèles LLM disponibles sur le cluster
        Schema::create('llm_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['local', 'api', 'hybrid'])->default('local');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->json('requirements'); // CPU, RAM, GPU nécessaires
            $table->json('config'); // Configuration du modèle
            $table->string('fallback_model')->nullable(); 
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Workers du cluster IA (liés aux audio_workers existants)
        Schema::create('llm_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_worker_id')->constrained('audio_workers')->onDelete('cascade');
            $table->enum('status', ['online', 'busy', 'offline', 'error'])->default('offline');
            $table->json('capabilities'); // Modèles supportés
            $table->integer('current_tokens_processed')->default(0);
            $table->integer('total_tokens_processed')->default(0);
            $table->integer('total_requests')->default(0);
            $table->float('avg_tokens_per_second')->default(0);
            $table->timestamp('last_heartbeat')->nullable();
            $table->timestamps();
        });

        // Jobs d'inférence LLM
        Schema::create('llm_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('llm_model_id')->constrained('llm_models')->onDelete('cascade');
            $table->foreignId('llm_worker_id')->nullable()->constrained('llm_workers')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->longText('prompt');
            $table->longText('response')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->float('tokens_per_second')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // température, max_tokens, etc.
            $table->timestamp('queued_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'queued_at']);
            $table->index(['llm_model_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_jobs');
        Schema::dropIfExists('llm_workers');
        Schema::dropIfExists('llm_models');
    }
};