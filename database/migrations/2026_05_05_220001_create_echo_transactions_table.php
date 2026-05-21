<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('echo_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->enum('type', [
                'purchase', 'donation', 'tip', 'withdrawal',
                'refund', 'commission', 'community_fund',
            ]);
            $table->enum('status', [
                'pending', 'completed', 'failed', 'cancelled',
            ])->default('pending');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('echo_amount', 15, 2)->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable();
            $table->foreignId('related_transaction_id')
                ->nullable()
                ->constrained('echo_transactions')
                ->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('stripe_payment_intent_id');
            $table->index('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echo_transactions');
    }
};
