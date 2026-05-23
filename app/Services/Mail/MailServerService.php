<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailServerService
{
    private string $baseUrl;
    private string $token;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.mail_server.url', ''), '/');
        $this->token = config('services.mail_server.token', '');
        $this->fromAddress = config('services.mail_server.from_address', 'contact@arborisis.com');
        $this->fromName = config('services.mail_server.from_name', 'Arborisis');
    }

    /**
     * Envoyer un email via le Mail Server
     */
    public function send(string $to, string $subject, ?string $html = null, ?string $text = null, ?string $replyTo = null): array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::error('MailServerService: URL or token not configured');
            throw new \RuntimeException('Mail server not configured');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', array_filter([
                'to' => $to,
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
                'replyTo' => $replyTo,
            ]));

            if ($response->successful()) {
                Log::info('MailServerService: Email sent successfully', [
                    'to' => $to,
                    'subject' => $subject,
                ]);
                return $response->json();
            }

            Log::error('MailServerService: Failed to send email', [
                'to' => $to,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            
            throw new \RuntimeException('Failed to send email: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('MailServerService: Exception', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Envoyer un email avec template
     */
    public function sendTemplate(string $to, string $template, array $data = []): array
    {
        if (empty($this->baseUrl) || empty($this->token)) {
            Log::error('MailServerService: URL or token not configured');
            throw new \RuntimeException('Mail server not configured');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send-template', [
                'to' => $to,
                'template' => $template,
                'data' => $data,
            ]);

            if ($response->successful()) {
                Log::info('MailServerService: Template email sent successfully', [
                    'to' => $to,
                    'template' => $template,
                ]);
                return $response->json();
            }

            Log::error('MailServerService: Failed to send template email', [
                'to' => $to,
                'template' => $template,
                'status' => $response->status(),
            ]);
            
            throw new \RuntimeException('Failed to send template email: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('MailServerService: Exception', [
                'to' => $to,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Vérifier la santé du service
     */
    public function health(): bool
    {
        if (empty($this->baseUrl)) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('MailServerService: Health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
