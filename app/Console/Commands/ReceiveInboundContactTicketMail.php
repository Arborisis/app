<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Contact\ContactTicketService;
use Illuminate\Console\Command;

class ReceiveInboundContactTicketMail extends Command
{
    protected $signature = 'contact:receive-inbound-mail';

    protected $description = 'Receive a raw inbound email from stdin and attach it to a contact ticket';

    public function handle(ContactTicketService $contactTicketService): int
    {
        $rawEmail = stream_get_contents(STDIN);

        if ($rawEmail === false || trim($rawEmail) === '') {
            return self::FAILURE;
        }

        $payload = $this->parseRawEmail($rawEmail);
        $contactTicketService->receiveInboundReply($payload);

        return self::SUCCESS;
    }

    private function parseRawEmail(string $rawEmail): array
    {
        [$rawHeaders, $rawBody] = array_pad(preg_split("/\r?\n\r?\n/", $rawEmail, 2), 2, '');
        $headers = $this->parseHeaders($rawHeaders);
        $contentType = strtolower($headers['content-type'] ?? '');

        return [
            'from' => $headers['from'] ?? '',
            'to' => $headers['to'] ?? '',
            'subject' => $this->decodeHeader($headers['subject'] ?? ''),
            'text' => $this->extractTextBody($rawBody, $contentType, $headers['content-transfer-encoding'] ?? ''),
            'headers' => $headers,
            'in_reply_to' => $headers['in-reply-to'] ?? '',
            'references' => $headers['references'] ?? '',
            'message_id' => $headers['message-id'] ?? '',
        ];
    }

    private function parseHeaders(string $rawHeaders): array
    {
        $unfolded = preg_replace("/\r?\n[ \t]+/", ' ', $rawHeaders) ?? $rawHeaders;
        $headers = [];

        foreach (preg_split("/\r?\n/", $unfolded) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }

    private function extractTextBody(string $rawBody, string $contentType, string $encoding): string
    {
        $boundary = $this->extractBoundary($contentType);

        if ($boundary === null) {
            return $this->decodeBody($rawBody, $encoding);
        }

        foreach (explode('--'.$boundary, $rawBody) as $part) {
            [$partHeaders, $partBody] = array_pad(preg_split("/\r?\n\r?\n/", ltrim($part), 2), 2, '');
            $headers = $this->parseHeaders($partHeaders);

            if (str_contains(strtolower($headers['content-type'] ?? ''), 'text/plain')) {
                return $this->decodeBody($partBody, $headers['content-transfer-encoding'] ?? '');
            }
        }

        return '';
    }

    private function extractBoundary(string $contentType): ?string
    {
        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function decodeBody(string $body, string $encoding): string
    {
        return match (strtolower(trim($encoding))) {
            'base64' => (string) base64_decode($body, true),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function decodeHeader(string $header): string
    {
        if (function_exists('iconv_mime_decode')) {
            return iconv_mime_decode($header, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $header;
        }

        return $header;
    }
}
