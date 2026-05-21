<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_progress')->default(0);
            $table->unsignedInteger('target_progress')->default(1);
            $table->string('status')->default('not_started'); // not_started | in_progress | completed | claimed | expired
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'quest_id']);
            $table->index(['user_id', 'status']);
            $table->index('quest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_progress');
    }
};
