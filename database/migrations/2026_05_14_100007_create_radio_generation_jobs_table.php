<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30)->index();
            $table->string('idempotency_key', 80)->nullable()->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->jsonb('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('channel_id')
                ->nullable()
                ->constrained('radio_channels', 'id', 'fk_radio_generation_jobs_channel')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['kind', 'status'], 'idx_radio_generation_jobs_kind_status');
            $table->index('completed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_generation_jobs');
    }
};
