<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'source',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->subscribed_at !== null && $this->unsubscribed_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('subscribed_at')->whereNull('unsubscribed_at');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->whereNotNull('unsubscribed_at');
    }
}
