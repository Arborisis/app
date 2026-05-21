<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

class InboundContactTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expected = config('services.contact.inbound_mail_token');
        $token = $this->bearerToken() ?? $this->header('X-Internal-Token');

        return ! empty($expected) && hash_equals((string) $expected, (string) $token);
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'string', 'max:512'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:512'],
            'text' => ['nullable', 'string', 'max:20000'],
            'body_plain' => ['nullable', 'string', 'max:20000'],
            'stripped_text' => ['nullable', 'string', 'max:20000'],
            'html' => ['nullable', 'string', 'max:50000'],
            'to' => ['nullable'],
            'recipient' => ['nullable', 'string', 'max:512'],
            'headers' => ['nullable'],
            'in_reply_to' => ['nullable', 'string', 'max:512'],
            'references' => ['nullable', 'string', 'max:2000'],
            'message_id' => ['nullable', 'string', 'max:512'],
        ];
    }
}
