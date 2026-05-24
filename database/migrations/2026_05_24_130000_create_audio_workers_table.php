<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('hostname')->unique();
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'online', 'offline', 'busy', 'error'])->default('pending');
            $table->integer('cpu_cores')->default(1);
            $table->integer('cpu_score')->nullable();
            $table->integer('memory_gb')->default(1);
            $table->boolean('has_gpu')->default(false);
            $table->string('gpu_model')->nullable();
            $table->string('os')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->integer('port')->default(8080);
            $table->string('cloudflare_tunnel_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->integer('total_jobs_completed')->default(0);
            $table->integer('total_jobs_failed')->default(0);
            $table->float('avg_processing_time')->default(0);
            $table->json('capabilities')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'last_seen_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_workers');
    }
};