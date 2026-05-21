<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type'); // visit | point_created | sound_uploaded | quest_completed | achievement_unlocked | report_valid | medal_unlocked
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('amount');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'source_type']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
    }
};
