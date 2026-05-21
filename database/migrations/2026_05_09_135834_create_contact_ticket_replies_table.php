<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reply');
            $table->timestamps();

            $table->index('contact_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_ticket_replies');
    }
};
