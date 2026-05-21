<?php

declare(strict_types=1);

namespace App\Services\Echo;

use App\Enums\EchoDonationType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\EchoDonation;
use App\Models\EchoTransaction;
use App\Models\Sound;
use App\Models\User;
use App\Services\Discord\DiscordNotificationService;
use Illuminate\Support\Facades\DB;

class DonationService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly DiscordNotificationService $discordNotification,
    ) {}

    public function donate(
        User $donor,
        User $recipient,
        ?Sound $sound,
        float $amount,
        EchoDonationType $type,
        ?string $message = null
    ): EchoDonation {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être positif.');
        }

        if ($donor->id === $recipient->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous donner des ECHO à vous-même.');
        }

        return DB::transaction(function () use ($donor, $recipient, $sound, $amount, $type, $message) {
            $this->walletService->debit($donor, $amount, 'Don ECHO');

            $creatorShare = round($amount * 0.70, 2);
            $platformShare = round($amount * 0.20, 2);
            $communityShare = round($amount * 0.10, 2);

            $transaction = EchoTransaction::create([
                'user_id' => $donor->id,
                'type' => TransactionType::Donation,
                'status' => TransactionStatus::Completed,
                'amount' => $amount,
                'currency' => 'ECHO',
                'echo_amount' => $amount,
                'completed_at' => now(),
                'metadata' => [
                    'recipient_id' => $recipient->id,
                    'sound_id' => $sound?->id,
                    'type' => $type->value,
                ],
            ]);

            $this->walletService->credit($recipient, $creatorShare, 'Don reçu');

            $creatorTransaction = EchoTransaction::create([
                'user_id' => $recipient->id,
                'type' => TransactionType::Donation,
                'status' => TransactionStatus::Completed,
                'amount' => $creatorShare,
                'currency' => 'ECHO',
                'echo_amount' => $creatorShare,
                'related_transaction_id' => $transaction->id,
                'completed_at' => now(),
                'metadata' => [
                    'donor_id' => $donor->id,
                    'sound_id' => $sound?->id,
                    'type' => $type->value,
                    'share_type' => 'creator',
                ],
            ]);

            EchoTransaction::create([
                'user_id' => $recipient->id,
                'type' => TransactionType::Commission,
                'status' => TransactionStatus::Completed,
                'amount' => $platformShare,
                'currency' => 'ECHO',
                'echo_amount' => $platformShare,
                'related_transaction_id' => $transaction->id,
                'completed_at' => now(),
                'metadata' => [
                    'donor_id' => $donor->id,
                    'sound_id' => $sound?->id,
                    'type' => $type->value,
                    'share_type' => 'platform',
                ],
            ]);

            EchoTransaction::create([
                'user_id' => $recipient->id,
                'type' => TransactionType::CommunityFund,
                'status' => TransactionStatus::Completed,
                'amount' => $communityShare,
                'currency' => 'ECHO',
                'echo_amount' => $communityShare,
                'related_transaction_id' => $transaction->id,
                'completed_at' => now(),
                'metadata' => [
                    'donor_id' => $donor->id,
                    'sound_id' => $sound?->id,
                    'type' => $type->value,
                    'share_type' => 'community',
                ],
            ]);

            $donation = EchoDonation::create([
                'donor_id' => $donor->id,
                'recipient_id' => $recipient->id,
                'sound_id' => $sound?->id,
                'type' => $type,
                'amount' => $amount,
                'creator_share' => $creatorShare,
                'platform_share' => $platformShare,
                'community_share' => $communityShare,
                'message' => $message,
                'transaction_id' => $transaction->id,
            ]);

            $this->discordNotification->notifyDonation($donation);

            return $donation;
        });
    }

    public function getDonationHistory(User $user, int $perPage = 15): array
    {
        $sent = EchoDonation::with(['recipient.profile', 'sound'])
            ->where('donor_id', $user->id)
            ->latest()
            ->paginate($perPage, ['*'], 'sent_page');

        $received = EchoDonation::with(['donor.profile', 'sound'])
            ->where('recipient_id', $user->id)
            ->latest()
            ->paginate($perPage, ['*'], 'received_page');

        return [
            'sent' => $sent,
            'received' => $received,
        ];
    }
}
