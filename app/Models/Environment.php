<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Environment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'order',
    ];

    protected static function booted(): void
    {
        static::creating(function (Environment $environment) {
            if (empty($environment->slug)) {
                $environment->slug = Str::slug($environment->name);
            }
        });
    }

    public function sounds(): HasMany
    {
        return $this->hasMany(Sound::class);
    }
}
