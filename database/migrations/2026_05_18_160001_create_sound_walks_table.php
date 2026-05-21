<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_walks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('visibility_status')->default('public'); // public | unlisted | private
            $table->string('moderation_status')->default('pending'); // pending | approved | rejected | hidden | archived
            $table->jsonb('route_geometry')->nullable();
            $table->decimal('start_latitude', 10, 8);
            $table->decimal('start_longitude', 11, 8);
            $table->decimal('approximate_start_latitude', 10, 8);
            $table->decimal('approximate_start_longitude', 11, 8);
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->unsignedTinyInteger('difficulty_level')->default(1); // 1-5
            $table->jsonb('tags')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('audio_environment_type')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['start_latitude', 'start_longitude']);
            $table->index(['approximate_start_latitude', 'approximate_start_longitude']);
            $table->index('user_id');
            $table->index('moderation_status');
            $table->index(['moderation_status', 'visibility_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_walks');
    }
};
