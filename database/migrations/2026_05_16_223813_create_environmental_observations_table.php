<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environmental_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listening_point_id')->nullable()->constrained()->nullOnDelete();

            // Temporel
            $table->string('season', 10)->nullable();
            $table->string('time_of_day', 10)->nullable();
            $table->decimal('sun_altitude_deg', 5, 2)->nullable();

            // Météo
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->unsignedTinyInteger('humidity_percent')->nullable();
            $table->decimal('wind_speed_kmh', 5, 1)->nullable();
            $table->string('wind_direction', 3)->nullable();
            $table->boolean('is_raining')->nullable();
            $table->boolean('is_snowing')->nullable();
            $table->string('weather_condition', 30)->nullable();

            // Qualité
            $table->string('source', 20)->default('user');
            $table->jsonb('raw_data')->nullable();

            $table->timestamps();

            $table->index('sound_id');
            $table->index('listening_point_id');
            $table->index('season');
            $table->index('time_of_day');
            $table->index('weather_condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environmental_observations');
    }
};
