<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token')->unique();
            $table->string('source')->nullable();
            $table->timestamp('subscribed_at');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index(['subscribed_at', 'unsubscribed_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
