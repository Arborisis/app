<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_walk_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sound_walk_id')->constrained()->cascadeOnDelete();
            $table->foreignId('arborisis_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedSmallInteger('order');
            $table->jsonb('stop_metadata')->nullable();
            $table->timestamps();

            $table->index('sound_walk_id');
            $table->index('arborisis_point_id');
            $table->unique(['sound_walk_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_walk_points');
    }
};
