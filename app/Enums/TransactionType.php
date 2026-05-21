<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Purchase = 'purchase';
    case Donation = 'donation';
    case Tip = 'tip';
    case Withdrawal = 'withdrawal';
    case Refund = 'refund';
    case Commission = 'commission';
    case CommunityFund = 'community_fund';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Achat',
            self::Donation => 'Don',
            self::Tip => 'Pourboire',
            self::Withdrawal => 'Retrait',
            self::Refund => 'Remboursement',
            self::Commission => 'Commission',
            self::CommunityFund => 'Fonds communautaire',
        };
    }
}
