<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsletterCampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'content_html',
        'content_text',
        'sent_at',
        'recipients_count',
        'opened_count',
        'clicked_count',
        'status',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'status' => NewsletterCampaignStatus::class,
            'recipients_count' => 'integer',
            'opened_count' => 'integer',
            'clicked_count' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isDraft(): bool
    {
        return $this->status === NewsletterCampaignStatus::Draft;
    }

    public function isSent(): bool
    {
        return $this->status === NewsletterCampaignStatus::Sent;
    }
}
