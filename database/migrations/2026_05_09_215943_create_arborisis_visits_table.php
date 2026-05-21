<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arborisis_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('arborisis_point_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('distance_from_point', 10, 2)->nullable(); // meters
            $table->timestamp('visited_at');
            $table->decimal('device_accuracy', 8, 2)->nullable(); // meters
            $table->string('status')->default('valid'); // valid | invalid | suspicious | under_review
            $table->string('validation_reason')->default('within_range');
            $table->decimal('anti_cheat_score', 5, 2)->default(100.00); // 0-100
            $table->text('anti_cheat_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'arborisis_point_id']);
            $table->index(['user_id', 'visited_at']);
            $table->index('arborisis_point_id');
            $table->index('status');
            $table->unique(['user_id', 'arborisis_point_id', 'visited_at'], 'unique_visit_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arborisis_visits');
    }
};
