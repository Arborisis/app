<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_recording_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamp('scheduled_at');
            $table->string('event_type', 32)->default('freestyle'); // dawn_chorus, soundwalk, night_ambience, freestyle
            $table->unsignedTinyInteger('max_participants')->default(10);
            $table->string('status', 16)->default('upcoming'); // upcoming, ongoing, completed, cancelled
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('group_recording_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('group_recording_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->string('status', 16)->default('joined'); // joined, checked_in, left
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_recording_participants');
        Schema::dropIfExists('group_recording_events');
    }
};
