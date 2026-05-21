<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('type');
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->string('status')->default('new');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('ticket_number');
            $table->index('type');
            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_tickets');
    }
};
