<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\HelpdeskTicketPriority;
use App\Enums\HelpdeskTicketStatus;
use App\Models\HelpdeskCategory;
use App\Models\HelpdeskTicket;
use App\Models\User;
use Tests\TestCase;

class HelpdeskTest extends TestCase
{
    public function test_authenticated_user_can_create_ticket(): void
    {
        $user = User::factory()->create();
        $category = HelpdeskCategory::factory()->create();

        $response = $this->actingAs($user)->post('/helpdesk', [
            'subject' => 'Problème de test',
            'body' => 'Description détaillée du problème',
            'priority' => HelpdeskTicketPriority::Normal->value,
            'category_id' => $category->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('helpdesk_tickets', [
            'subject' => 'Problème de test',
            'user_id' => $user->id,
            'status' => HelpdeskTicketStatus::Open->value,
        ]);
    }

    public function test_guest_cannot_access_helpdesk(): void
    {
        $response = $this->get('/helpdesk');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/helpdesk/{$ticket->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_other_user_ticket(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/helpdesk/{$ticket->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_view_any_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherUser = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($admin)->get("/helpdesk/{$ticket->id}");

        $response->assertOk();
    }

    public function test_agent_can_reply_to_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/helpdesk/{$ticket->id}/reply", [
            'body' => 'Ma réponse',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('helpdesk_replies', [
            'body' => 'Ma réponse',
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_closed_ticket_cannot_receive_reply(): void
    {
        $user = User::factory()->create();
        $ticket = HelpdeskTicket::factory()->create([
            'user_id' => $user->id,
            'status' => HelpdeskTicketStatus::Closed,
        ]);

        $response = $this->actingAs($user)->post("/helpdesk/{$ticket->id}/reply", [
            'body' => 'Réponse interdite',
        ]);

        $response->assertForbidden();
    }
}
