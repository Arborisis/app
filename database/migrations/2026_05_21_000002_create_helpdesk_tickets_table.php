<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('helpdesk_categories')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('body');
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('normal');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('ticket_number');
            $table->index('status');
            $table->index('priority');
            $table->index(['user_id', 'status']);
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_tickets');
    }
};
