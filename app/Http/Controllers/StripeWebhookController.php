<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Echo\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeCheckoutService $stripeService
    ) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $this->stripeService->handleWebhook($payload, $sigHeader);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Webhook error', 500);
        }

        return response('Webhook handled', 200);
    }
}
