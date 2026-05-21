<?php

declare(strict_types=1);

namespace App\Services\Echo;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\EchoTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeCheckoutService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
    }

    public function createSession(User $user, float $amount, string $successUrl, string $cancelUrl): Session
    {
        return DB::transaction(function () use ($user, $amount, $successUrl, $cancelUrl) {
            $echoAmount = $this->calculateEchoAmount($amount);

            $transaction = EchoTransaction::create([
                'user_id' => $user->id,
                'type' => TransactionType::Purchase,
                'status' => TransactionStatus::Pending,
                'amount' => $amount,
                'currency' => 'EUR',
                'echo_amount' => $echoAmount,
                'metadata' => [
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                ],
            ]);

            $session = Session::create([
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => 'Crédits ECHO',
                                'description' => sprintf('%.2f ECHO', $echoAmount),
                            ],
                            'unit_amount' => (int) ($amount * 100),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => (string) $transaction->id,
                'customer_email' => $user->email,
                'metadata' => [
                    'transaction_id' => (string) $transaction->id,
                    'user_id' => (string) $user->id,
                ],
            ]);

            $transaction->update([
                'stripe_checkout_session_id' => $session->id,
            ]);

            return $session;
        });
    }

    public function handleWebhook(string $payload, string $sigHeader): void
    {
        $event = Webhook::constructEvent(
            $payload,
            $sigHeader,
            config('services.stripe.webhook_secret')
        );

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
    }

    private function handleCheckoutSessionCompleted(Session $session): void
    {
        $pendingTransaction = EchoTransaction::where('stripe_checkout_session_id', $session->id)
            ->where('status', TransactionStatus::Pending)
            ->first();

        if (! $pendingTransaction) {
            return;
        }

        // Idempotence : vérifier qu'aucune transaction complétée n'existe déjà pour cette session
        $alreadyProcessed = EchoTransaction::where('related_transaction_id', $pendingTransaction->id)
            ->where('status', TransactionStatus::Completed)
            ->exists();

        if ($alreadyProcessed) {
            return;
        }

        DB::transaction(function () use ($pendingTransaction, $session) {
            EchoTransaction::create([
                'user_id' => $pendingTransaction->user_id,
                'type' => TransactionType::Purchase,
                'status' => TransactionStatus::Completed,
                'amount' => $pendingTransaction->amount,
                'currency' => $pendingTransaction->currency,
                'echo_amount' => $pendingTransaction->echo_amount,
                'stripe_payment_intent_id' => $session->payment_intent,
                'stripe_checkout_session_id' => $session->id,
                'related_transaction_id' => $pendingTransaction->id,
                'completed_at' => now(),
                'metadata' => array_merge(
                    $pendingTransaction->metadata ?? [],
                    ['source' => 'stripe_webhook', 'original_transaction_id' => $pendingTransaction->id]
                ),
            ]);

            $walletService = app(WalletService::class);
            $walletService->credit(
                $pendingTransaction->user,
                (float) $pendingTransaction->echo_amount,
                'Achat ECHO via Stripe'
            );
        });
    }

    private function handleCheckoutSessionExpired(Session $session): void
    {
        $pendingTransaction = EchoTransaction::where('stripe_checkout_session_id', $session->id)
            ->where('status', TransactionStatus::Pending)
            ->first();

        if (! $pendingTransaction) {
            return;
        }

        $alreadyProcessed = EchoTransaction::where('related_transaction_id', $pendingTransaction->id)
            ->where('status', TransactionStatus::Cancelled)
            ->exists();

        if ($alreadyProcessed) {
            return;
        }

        DB::transaction(function () use ($pendingTransaction, $session) {
            EchoTransaction::create([
                'user_id' => $pendingTransaction->user_id,
                'type' => TransactionType::Purchase,
                'status' => TransactionStatus::Cancelled,
                'amount' => $pendingTransaction->amount,
                'currency' => $pendingTransaction->currency,
                'echo_amount' => $pendingTransaction->echo_amount,
                'stripe_checkout_session_id' => $session->id,
                'related_transaction_id' => $pendingTransaction->id,
                'metadata' => array_merge(
                    $pendingTransaction->metadata ?? [],
                    ['source' => 'stripe_webhook', 'original_transaction_id' => $pendingTransaction->id]
                ),
            ]);
        });
    }

    private function handlePaymentFailed(PaymentIntent $paymentIntent): void
    {
        $pendingTransaction = EchoTransaction::where('stripe_payment_intent_id', $paymentIntent->id)
            ->where('status', TransactionStatus::Pending)
            ->first();

        if (! $pendingTransaction) {
            return;
        }

        $alreadyProcessed = EchoTransaction::where('related_transaction_id', $pendingTransaction->id)
            ->where('status', TransactionStatus::Failed)
            ->exists();

        if ($alreadyProcessed) {
            return;
        }

        DB::transaction(function () use ($pendingTransaction, $paymentIntent) {
            EchoTransaction::create([
                'user_id' => $pendingTransaction->user_id,
                'type' => TransactionType::Purchase,
                'status' => TransactionStatus::Failed,
                'amount' => $pendingTransaction->amount,
                'currency' => $pendingTransaction->currency,
                'echo_amount' => $pendingTransaction->echo_amount,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'related_transaction_id' => $pendingTransaction->id,
                'metadata' => array_merge(
                    $pendingTransaction->metadata ?? [],
                    ['source' => 'stripe_webhook', 'original_transaction_id' => $pendingTransaction->id]
                ),
            ]);
        });
    }

    private function calculateEchoAmount(float $eurAmount): float
    {
        return round($eurAmount * 10, 2);
    }
}
