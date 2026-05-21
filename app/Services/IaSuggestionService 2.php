<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HelpdeskIaSuggestion;
use App\Models\HelpdeskTicket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IaSuggestionService
{
    private const SYLVE_ENDPOINT = '/api/ai-agent/chat';

    public function generateSuggestion(HelpdeskTicket $ticket): ?HelpdeskIaSuggestion
    {
        try {
            $prompt = $this->buildPrompt($ticket);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-CSRF-TOKEN' => csrf_token(),
            ])
                ->withCookies(request()->cookies->all(), request()->getHost())
                ->post(url(self::SYLVE_ENDPOINT), [
                    'message' => $prompt,
                    'history' => [],
                    'page' => [
                        'url' => '/admin/helpdesk',
                        'title' => 'Helpdesk Arborisis',
                        'section' => 'helpdesk',
                    ],
                ]);

            if (! $response->ok()) {
                Log::warning('[IaSuggestionService] Sylve returned non-OK status', [
                    'ticket_id' => $ticket->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();
            $jobId = $data['job_id'] ?? null;

            if (! $jobId) {
                Log::warning('[IaSuggestionService] Missing job_id from Sylve', [
                    'ticket_id' => $ticket->id,
                ]);

                return null;
            }

            $answer = $this->pollForResult($jobId);

            if (! $answer) {
                return null;
            }

            return HelpdeskIaSuggestion::create([
                'ticket_id' => $ticket->id,
                'suggested_body' => $answer,
                'model_used' => 'sylve-agent',
                'metadata' => [
                    'job_id' => $jobId,
                    'ticket_number' => $ticket->ticket_number,
                    'prompt_tokens' => null,
                ],
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::error('[IaSuggestionService] Failed to generate suggestion', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function validateSuggestion(HelpdeskIaSuggestion $suggestion, int $userId, ?string $editedBody = null): void
    {
        $suggestion->markValidated($userId);

        Log::info('[IaSuggestionService] Suggestion validated by human', [
            'suggestion_id' => $suggestion->id,
            'ticket_id' => $suggestion->ticket_id,
            'validator_id' => $userId,
            'was_edited' => $editedBody !== null,
        ]);
    }

    public function rejectSuggestion(HelpdeskIaSuggestion $suggestion, int $userId, string $reason): void
    {
        $suggestion->markRejected($reason, $userId);

        Log::info('[IaSuggestionService] Suggestion rejected by human', [
            'suggestion_id' => $suggestion->id,
            'ticket_id' => $suggestion->ticket_id,
            'validator_id' => $userId,
            'reason' => $reason,
        ]);
    }

    private function buildPrompt(HelpdeskTicket $ticket): string
    {
        $category = $ticket->category?->name ?? 'Non catégorisé';

        return sprintf(
            "Tu es Sylve, l'agent support d'Arborisis. Un utilisateur a ouvert un ticket de support.\n\n" .
            "Numéro : %s\n" .
            "Catégorie : %s\n" .
            "Priorité : %s\n" .
            "Sujet : %s\n" .
            "Description : %s\n\n" .
            "Rédige une réponse professionnelle, empathique et concise en français. " .
            "Ne pose pas de questions, propose directement une solution ou les prochaines étapes. " .
            "Signe la réponse 'Sylve — Équipe Arborisis'.",
            $ticket->ticket_number,
            $category,
            $ticket->priority->label(),
            $ticket->subject,
            $ticket->body
        );
    }

    private function pollForResult(string $jobId): ?string
    {
        $maxAttempts = 60;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            sleep(2);
            $attempts++;

            try {
                $res = Http::withHeaders([
                    'Accept' => 'application/json',
                ])
                    ->withCookies(request()->cookies->all(), request()->getHost())
                    ->get(url("/api/ai-agent/status/{$jobId}"));

                if (! $res->ok()) {
                    continue;
                }

                $status = $res->json();

                if ($status['status'] === 'completed') {
                    return $status['answer'] ?? null;
                }

                if ($status['status'] === 'failed') {
                    Log::warning('[IaSuggestionService] Job failed', [
                        'job_id' => $jobId,
                        'error' => $status['error'] ?? 'Unknown',
                    ]);

                    return null;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        Log::warning('[IaSuggestionService] Polling timeout', ['job_id' => $jobId]);

        return null;
    }
}
