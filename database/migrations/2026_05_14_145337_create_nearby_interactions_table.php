<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nearby_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32); // greet, share_tip, invite_event
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['initiator_id', 'recipient_id', 'created_at']);
            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearby_interactions');
    }
};
