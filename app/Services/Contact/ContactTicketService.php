<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\Enums\ContactTicketCategory;
use App\Enums\ContactTicketPriority;
use App\Enums\ContactTicketReplySource;
use App\Enums\ContactTicketStatus;
use App\Mail\ContactTicketReceived;
use App\Mail\ContactTicketSubmitted;
use App\Models\ContactTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactTicketService
{
    public function generateTicketNumber(): string
    {
        return 'ARB-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }

    public function create(array $data, ?User $user): ContactTicket
    {
        $ticket = ContactTicket::create([
            'ticket_number' => $this->generateTicketNumber(),
            'type' => $data['type'],
            'category' => ContactTicketCategory::General,
            'priority' => ContactTicketPriority::Medium,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => ContactTicketStatus::New,
            'user_id' => $user?->id,
        ]);

        $this->sendNotificationEmail($ticket);
        $this->sendReceiptEmail($ticket);

        return $ticket;
    }

    public function sendNotificationEmail(ContactTicket $ticket): void
    {
        $recipient = $ticket->type->recipientEmail();

        Mail::to($recipient)->queue(new ContactTicketSubmitted($ticket));
    }

    public function sendReceiptEmail(ContactTicket $ticket): void
    {
        Mail::to($ticket->email)->queue(new ContactTicketReceived($ticket));
    }

    public function receiveInboundReply(array $data): ?ContactTicket
    {
        if ($this->isAutomatedEmail($data)) {
            Log::info('Inbound contact ticket reply ignored: automated email.');

            return null;
        }

        $ticketNumber = $this->extractTicketNumber($data);
        $body = $this->extractReplyBody($data);

        if ($ticketNumber === null) {
            Log::info('Inbound contact ticket reply ignored: missing ticket number.', [
                'from' => $this->normalizeEmail((string) (($data['from'] ?? null) ?: ($data['from_email'] ?? ''))),
                'subject' => $data['subject'] ?? null,
            ]);

            return null;
        }

        if ($body === null) {
            Log::info('Inbound contact ticket reply ignored: empty body.', [
                'ticket_number' => $ticketNumber,
                'from' => $this->normalizeEmail((string) (($data['from'] ?? null) ?: ($data['from_email'] ?? ''))),
            ]);

            return null;
        }

        $fromEmail = $this->normalizeEmail((string) (($data['from'] ?? null) ?: ($data['from_email'] ?? '')));

        return DB::transaction(function () use ($ticketNumber, $body, $fromEmail): ?ContactTicket {
            $ticket = ContactTicket::query()
                ->where('ticket_number', $ticketNumber)
                ->lockForUpdate()
                ->first();

            if ($ticket === null || strcasecmp($ticket->email, $fromEmail) !== 0) {
                Log::info('Inbound contact ticket reply ignored: ticket missing or sender mismatch.', [
                    'ticket_number' => $ticketNumber,
                    'from' => $fromEmail,
                    'ticket_found' => $ticket !== null,
                ]);

                return null;
            }

            $ticket->replies()->create([
                'source' => ContactTicketReplySource::Customer,
                'reply' => $body,
            ]);

            $ticket->forceFill([
                'status' => ContactTicketStatus::InProgress,
                'replied_at' => now(),
            ])->save();

            return $ticket;
        });
    }

    private function extractTicketNumber(array $data): ?string
    {
        $haystack = [
            $data['subject'] ?? null,
            $data['to'] ?? null,
            $data['recipient'] ?? null,
            $data['in_reply_to'] ?? null,
            $data['references'] ?? null,
            $data['headers'] ?? null,
        ];

        $text = collect($haystack)
            ->map(fn (mixed $value): string => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value)
            ->implode(' ');

        if (preg_match('/ARB-\d{8}-[A-Z0-9]{5}/i', $text, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[0]);
    }

    private function extractReplyBody(array $data): ?string
    {
        $body = (string) ($data['text'] ?? $data['body_plain'] ?? $data['stripped_text'] ?? '');

        if ($body === '' && ! empty($data['html'])) {
            $body = trim(strip_tags((string) $data['html']));
        }

        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = preg_split('/\n(?:On|Le) .+(?:wrote|a écrit)\s?:/iu', $body, 2)[0] ?? $body;
        $body = preg_split('/\n-{2,}\s*(?:Original Message|Message d[’\']origine)\s*-{2,}/iu', $body, 2)[0] ?? $body;

        $lines = array_filter(
            array_map('rtrim', explode("\n", $body)),
            fn (string $line): bool => ! str_starts_with(ltrim($line), '>')
        );

        $body = trim(implode("\n", $lines));

        return $body === '' ? null : Str::limit($body, 5000, '');
    }

    private function isAutomatedEmail(array $data): bool
    {
        $headers = strtolower(json_encode($data['headers'] ?? [], JSON_THROW_ON_ERROR));

        return str_contains($headers, 'auto-submitted')
            || str_contains($headers, 'x-auto-response-suppress')
            || str_contains($headers, 'precedence: bulk')
            || str_contains(strtolower((string) ($data['from'] ?? '')), 'mailer-daemon');
    }

    private function normalizeEmail(string $value): string
    {
        if (preg_match('/<([^>]+)>/', $value, $matches) === 1) {
            return strtolower(trim($matches[1]));
        }

        return strtolower(trim($value));
    }
}
