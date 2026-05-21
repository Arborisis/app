<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_ia_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->text('suggested_body');
            $table->string('model_used')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ia_suggestions');
    }
};
