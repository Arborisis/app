<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_internal_note')->default(false);
            $table->boolean('is_ai_generated')->default(false);
            $table->boolean('is_ai_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('is_ai_generated');
            $table->index('is_ai_validated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_replies');
    }
};
