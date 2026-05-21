<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\EchoDonationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EchoDonation\StoreEchoDonationRequest;
use App\Models\EchoDonation;
use App\Models\Sound;
use App\Models\User;
use App\Services\Echo\DonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EchoDonationController extends Controller
{
    public function __construct(
        private readonly DonationService $donationService
    ) {}

    public function store(StoreEchoDonationRequest $request): RedirectResponse
    {
        $this->authorize('create', EchoDonation::class);

        $donor = $request->user();
        $validated = $request->validated();

        $recipient = User::findOrFail($validated['recipient_id']);
        $sound = isset($validated['sound_id'])
            ? Sound::findOrFail($validated['sound_id'])
            : null;

        $this->donationService->donate(
            $donor,
            $recipient,
            $sound,
            (float) $validated['amount'],
            EchoDonationType::from($validated['type']),
            $validated['message'] ?? null
        );

        return back()->with('success', 'Votre don a été envoyé avec succès.');
    }

    public function history(Request $request): Response
    {
        $user = $request->user();

        $this->authorize('view', $user);

        $history = $this->donationService->getDonationHistory($user);

        return Inertia::render('Wallet/DonationHistory', [
            'sent' => $history['sent'],
            'received' => $history['received'],
        ]);
    }
}
