<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('session_token', 80);
            $table->foreignId('sound_id')->constrained()->cascadeOnDelete();
            $table->string('reaction_type', 24);
            $table->timestamps();

            $table->unique(['session_token', 'sound_id', 'reaction_type'], 'radio_reactions_unique_session_sound_type');
            $table->index(['sound_id', 'reaction_type', 'created_at'], 'radio_reactions_sound_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_reactions');
    }
};
