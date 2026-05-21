<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('discovery');
            $table->string('category')->default('general');
            $table->string('objective_type');
            $table->unsignedInteger('objective_target')->default(1);
            $table->jsonb('objective_payload')->nullable();
            $table->unsignedInteger('reward_xp')->default(0);
            $table->foreignId('reward_medal_id')->nullable()->constrained('medals')->nullOnDelete();
            $table->foreignId('reward_achievement_id')->nullable()->constrained('achievements')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('objective_type');
            $table->index('is_active');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quests');
    }
};
