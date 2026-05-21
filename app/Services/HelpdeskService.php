<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HelpdeskTicketStatus;
use App\Models\HelpdeskCategory;
use App\Models\HelpdeskReply;
use App\Models\HelpdeskTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class HelpdeskService
{
    public function createTicket(User $user, array $data): HelpdeskTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $ticket = HelpdeskTicket::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'ticket_number' => $this->generateTicketNumber(),
                'subject' => $data['subject'],
                'body' => $data['body'],
                'status' => HelpdeskTicketStatus::Open,
                'priority' => $data['priority'],
            ]);

            return $ticket;
        });
    }

    public function addReply(HelpdeskTicket $ticket, User $user, string $body, bool $isInternal = false): HelpdeskReply
    {
        return DB::transaction(function () use ($ticket, $user, $body, $isInternal) {
            if (! $isInternal && $ticket->status === HelpdeskTicketStatus::Open) {
                $ticket->markInProgress();
            }

            return HelpdeskReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'body' => $body,
                'is_internal_note' => $isInternal,
            ]);
        });
    }

    public function addAiValidatedReply(HelpdeskTicket $ticket, User $validator, string $body): HelpdeskReply
    {
        return DB::transaction(function () use ($ticket, $validator, $body) {
            if ($ticket->status === HelpdeskTicketStatus::Open) {
                $ticket->markInProgress();
            }

            return HelpdeskReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $validator->id,
                'body' => $body,
                'is_ai_generated' => true,
                'is_ai_validated' => true,
                'validated_by' => $validator->id,
                'validated_at' => now(),
            ]);
        });
    }

    public function resolveTicket(HelpdeskTicket $ticket, ?User $resolver = null): void
    {
        DB::transaction(function () use ($ticket, $resolver) {
            $ticket->markResolved();

            if ($resolver) {
                $ticket->update(['assigned_to' => $resolver->id]);
            }
        });
    }

    public function closeTicket(HelpdeskTicket $ticket): void
    {
        $ticket->markClosed();
    }

    public function reopenTicket(HelpdeskTicket $ticket): void
    {
        $ticket->update([
            'status' => HelpdeskTicketStatus::InProgress,
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    public function assignTicket(HelpdeskTicket $ticket, User $agent): void
    {
        $ticket->update(['assigned_to' => $agent->id]);
    }

    /**
     * @return LengthAwarePaginator<HelpdeskTicket>
     */
    public function listTicketsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = HelpdeskTicket::with(['category', 'user'])
            ->forUser($user->id)
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<HelpdeskTicket>
     */
    public function listAllTickets(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = HelpdeskTicket::with(['category', 'user', 'assignee'])
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, HelpdeskCategory>
     */
    public function getActiveCategories(): Collection
    {
        return HelpdeskCategory::active()->orderBy('sort_order')->get();
    }

    private function generateTicketNumber(): string
    {
        $prefix = 'ARB';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$date}-{$random}";
    }
}
