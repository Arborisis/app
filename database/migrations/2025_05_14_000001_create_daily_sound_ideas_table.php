<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_sound_ideas', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('theme', 120)->nullable();
            $table->string('title', 120);
            $table->text('description');
            $table->string('difficulty', 20)->default('easy');
            $table->jsonb('tags')->nullable();
            $table->string('season_context', 30)->nullable();
            $table->string('weather_context', 40)->nullable();
            $table->string('time_of_day', 20)->nullable();
            $table->timestamps();

            $table->unique(['date', 'title']);
        });

        Schema::create('user_sound_idea_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_sound_idea_id')->constrained('daily_sound_ideas')->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'daily_sound_idea_id'], 'unique_user_idea_progress');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sound_idea_progress');
        Schema::dropIfExists('daily_sound_ideas');
    }
};
