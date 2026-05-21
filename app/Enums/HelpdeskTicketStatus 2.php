<?php

declare(strict_types=1);

namespace App\Enums;

enum HelpdeskTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouvert',
            self::InProgress => 'En cours',
            self::Resolved => 'Résolu',
            self::Closed => 'Fermé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::InProgress => 'cyan',
            self::Resolved => 'emerald',
            self::Closed => 'slate',
        };
    }

    public function canReply(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }
}
