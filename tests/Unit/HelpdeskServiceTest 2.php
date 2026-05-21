<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\HelpdeskTicketPriority;
use App\Enums\HelpdeskTicketStatus;
use App\Models\HelpdeskTicket;
use App\Models\User;
use App\Services\HelpdeskService;
use Tests\TestCase;

class HelpdeskServiceTest extends TestCase
{
    public function test_ticket_number_is_generated(): void
    {
        $service = app(HelpdeskService::class);
        $user = User::factory()->create();

        $ticket = $service->createTicket($user, [
            'subject' => 'Test',
            'body' => 'Body',
            'priority' => HelpdeskTicketPriority::Normal->value,
        ]);

        $this->assertStringStartsWith('ARB-', $ticket->ticket_number);
        $this->assertSame(HelpdeskTicketStatus::Open, $ticket->fresh()->status);
    }

    public function test_resolving_ticket_sets_timestamp(): void
    {
        $service = app(HelpdeskService::class);
        $user = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create(['user_id' => $user->id]);

        $service->resolveTicket($ticket);

        $this->assertSame(HelpdeskTicketStatus::Resolved, $ticket->fresh()->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_ai_reply_is_flagged_correctly(): void
    {
        $service = app(HelpdeskService::class);
        $user = User::factory()->create();
        $agent = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create(['user_id' => $user->id]);

        $reply = $service->addAiValidatedReply($ticket, $agent, 'Réponse IA');

        $this->assertTrue($reply->is_ai_generated);
        $this->assertTrue($reply->is_ai_validated);
        $this->assertSame($agent->id, $reply->validated_by);
    }
}
