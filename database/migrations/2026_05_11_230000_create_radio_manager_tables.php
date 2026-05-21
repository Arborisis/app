<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('repeat', 30)->default('none')->index();
            $table->unsignedSmallInteger('priority')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('radio_schedule_sound', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radio_schedule_id')
                ->constrained('radio_schedules', 'id', 'fk_radio_schedule_sound_schedule')
                ->cascadeOnDelete();
            $table->foreignId('sound_id')
                ->constrained('sounds', 'id', 'fk_radio_schedule_sound_sound')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0)->index();
            $table->timestamps();

            $table->unique(['radio_schedule_id', 'sound_id'], 'radio_schedule_sound_unique');
        });

        Schema::create('radio_jingles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('disk', 30)->default('audio');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->string('placement', 30)->default('between_blocks')->index();
            $table->unsignedSmallInteger('frequency')->default(3);
            $table->decimal('volume', 4, 2)->default(1.00);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('radio_settings', function (Blueprint $table) {
            $table->id();
            $table->string('station_name')->default('Arborisis Radio');
            $table->string('tagline')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('shuffle_enabled')->default(true);
            $table->boolean('loop_enabled')->default(true);
            $table->unsignedInteger('gap_ms')->default(500);
            $table->unsignedInteger('icy_metaint')->default(8192);
            $table->unsignedSmallInteger('history_limit')->default(20);
            $table->unsignedSmallInteger('max_listeners_display')->nullable();
            $table->unsignedInteger('listener_ttl_seconds')->default(120);
            $table->timestamps();
        });

        DB::table('radio_settings')->insert([
            'station_name' => 'Arborisis Radio',
            'tagline' => 'Field recordings et sons de nature en continu',
            'is_enabled' => true,
            'shuffle_enabled' => true,
            'loop_enabled' => true,
            'gap_ms' => 500,
            'icy_metaint' => 8192,
            'history_limit' => 20,
            'max_listeners_display' => null,
            'listener_ttl_seconds' => 120,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_settings');
        Schema::dropIfExists('radio_jingles');
        Schema::dropIfExists('radio_schedule_sound');
        Schema::dropIfExists('radio_schedules');
    }
};
