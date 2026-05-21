<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echo_donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('recipient_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('sound_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');
            $table->enum('type', ['tip', 'support'])
                ->default('tip');
            $table->decimal('amount', 15, 2);
            $table->decimal('creator_share', 15, 2);
            $table->decimal('platform_share', 15, 2);
            $table->decimal('community_share', 15, 2);
            $table->text('message')->nullable();
            $table->foreignId('transaction_id')
                ->constrained('echo_transactions')
                ->onDelete('cascade');
            $table->timestamps();

            $table->index(['recipient_id', 'created_at']);
            $table->index(['donor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_donations');
    }
};
