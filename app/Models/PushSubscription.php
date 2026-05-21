<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PushSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'endpoint',
        'p256dh',
        'auth',
        'user_agent',
        'ip_address',
        'subscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];
}
