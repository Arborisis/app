<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LlmModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'status',
        'requirements', 'config', 'fallback_model', 'is_default'
    ];

    protected $casts = [
        'requirements' => 'array',
        'config' => 'array',
        'is_default' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(LlmJob::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getFallbackModel(): ?self
    {
        if ($this->fallback_model) {
            return self::where('slug', $this->fallback_model)->first();
        }
        return null;
    }
}