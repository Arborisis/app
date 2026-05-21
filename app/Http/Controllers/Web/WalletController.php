<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreCheckoutRequest;
use App\Models\Wallet;
use App\Services\Echo\StripeCheckoutService;
use App\Services\Echo\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly StripeCheckoutService $stripeService
    ) {}

    public function show(Request $request): Response
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);

        $transactions = $user->echoTransactions()
            ->with('relatedTransaction')
            ->latest()
            ->paginate(20);

        return Inertia::render('Wallet/Show', [
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function checkout(StoreCheckoutRequest $request): SymfonyResponse
    {
        $this->authorize('checkout', Wallet::class);

        $user = $request->user();
        $amount = (float) $request->validated('amount');

        $session = $this->stripeService->createSession(
            $user,
            $amount,
            route('wallet.success', [], true),
            route('wallet.cancel', [], true)
        );

        return Inertia::location($session->url);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Wallet/Success');
    }

    public function cancel(Request $request): Response
    {
        return Inertia::render('Wallet/Cancel');
    }
}
