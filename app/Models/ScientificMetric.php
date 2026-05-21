<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ScientificMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurable_type',
        'measurable_id',
        'metric_type',
        'granularity',
        'period_key',
        'value',
        'value_distribution',
        'parameters',
        'components',
        'computed_at',
        'sample_size',
        'status',
        'computation_notes',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'value_distribution' => 'array',
        'parameters' => 'array',
        'components' => 'array',
        'computed_at' => 'datetime',
        'sample_size' => 'integer',
    ];

    public function measurable(): MorphTo
    {
        return $this->morphTo();
    }
}
