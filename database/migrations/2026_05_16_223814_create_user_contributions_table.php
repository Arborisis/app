<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listening_point_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('recordings_count')->default(0);
            $table->unsignedInteger('species_found_count')->default(0);
            $table->decimal('total_duration_seconds', 10, 2)->default(0);
            $table->timestamp('first_contribution_at')->nullable();
            $table->timestamp('last_contribution_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'listening_point_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_contributions');
    }
};
