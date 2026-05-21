<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_podcasts', function (Blueprint $table) {
            $table->id();
            $table->string('status', 30)->default('pending')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->jsonb('script_json')->nullable();
            $table->text('tts_text')->nullable();
            $table->string('voice_provider', 30)->default('elevenlabs');
            $table->string('voice_id')->nullable();
            $table->string('disk', 30)->default('radio_cache');
            $table->string('path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('target_duration_seconds')->default(180);
            $table->decimal('actual_duration_seconds', 8, 2)->nullable();
            $table->jsonb('sound_ids')->nullable();
            $table->unsignedInteger('generation_cost_cents')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_podcasts');
    }
};
