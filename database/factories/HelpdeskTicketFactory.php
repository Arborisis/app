<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HelpdeskTicketPriority;
use App\Enums\HelpdeskTicketStatus;
use App\Models\HelpdeskTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpdeskTicketFactory extends Factory
{
    protected $model = HelpdeskTicket::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'ticket_number' => 'ARB-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'status' => HelpdeskTicketStatus::Open,
            'priority' => fake()->randomElement(HelpdeskTicketPriority::cases()),
            'resolved_at' => null,
            'closed_at' => null,
            'assigned_to' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => HelpdeskTicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => HelpdeskTicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
