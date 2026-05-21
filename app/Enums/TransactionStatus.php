<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Completed => 'Terminée',
            self::Failed => 'Échouée',
            self::Cancelled => 'Annulée',
        };
    }
}
