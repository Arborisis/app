<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpeciesFact extends Model
{
    use HasFactory;

    protected $fillable = [
        'latin_name',
        'common_name_fr',
        'group',
        'fact_fr',
        'habitat',
        'seasonality',
        'source',
    ];

    public static function findByLatin(string $latin): ?self
    {
        return static::query()
            ->whereRaw('LOWER(latin_name) = ?', [mb_strtolower(trim($latin))])
            ->first();
    }

    public static function findByCommonNameFr(string $name): ?self
    {
        return static::query()
            ->whereRaw('LOWER(common_name_fr) = ?', [mb_strtolower(trim($name))])
            ->first();
    }
}
