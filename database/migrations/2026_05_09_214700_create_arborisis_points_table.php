<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arborisis_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('approximate_latitude', 10, 8);
            $table->decimal('approximate_longitude', 11, 8);
            $table->string('visibility_status')->default('public'); // public | unlisted | private
            $table->string('moderation_status')->default('pending'); // pending | approved | rejected | hidden | archived
            $table->string('category'); // ArborisisCategory enum
            $table->jsonb('tags')->nullable();
            $table->unsignedTinyInteger('difficulty_level')->default(1); // 1-5
            $table->string('nature_sensitivity_level')->default('normal'); // NatureSensitivityLevel enum
            $table->string('recommended_time')->nullable(); // e.g. "dawn", "morning", "all_day"
            $table->string('audio_environment_type')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['latitude', 'longitude']);
            $table->index(['approximate_latitude', 'approximate_longitude']);
            $table->index('user_id');
            $table->index('moderation_status');
            $table->index('category');
            $table->index('nature_sensitivity_level');
            $table->index(['moderation_status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arborisis_points');
    }
};
