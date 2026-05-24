<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['local', 'api', 'hybrid'])->default('local');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->json('requirements'); // CPU, RAM, GPU nécessaires
            $table->json('config'); // Configuration du modèle
            $table->string('fallback_model')->nullable(); // Modèle de fallback
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('cluster_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_model_id')->constrained('cluster_models')->onDelete('cascade');
            $table->foreignId('audio_worker_id')->constrained('audio_workers')->onDelete('cascade');
            $table->enum('type', ['inference', 'training', 'embedding', 'analysis']);
            $table->enum('status', ['queued', 'assigned', 'processing', 'completed', 'failed'])->default('queued');
            $table->json('payload'); // Données de la tâche
            $table->json('result')->nullable(); // Résultat
            $table->integer('processing_time_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'queued_at']);
            $table->index(['cluster_model_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_tasks');
        Schema::dropIfExists('cluster_models');
    }
};