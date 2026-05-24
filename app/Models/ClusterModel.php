<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClusterModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'status',
        'requirements',
        'config',
        'fallback_model',
        'is_default',
    ];

    protected $casts = [
        'requirements' => 'array',
        'config' => 'array',
        'is_default' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(ClusterTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function canRunOnWorker(AudioWorker $worker): bool
    {
        $reqs = $this->requirements;
        
        if (isset($reqs['min_cpu_cores']) && $worker->cpu_cores < $reqs['min_cpu_cores']) {
            return false;
        }
        
        if (isset($reqs['min_memory_gb']) && $worker->memory_gb < $reqs['min_memory_gb']) {
            return false;
        }
        
        if (($reqs['requires_gpu'] ?? false) && !$worker->has_gpu) {
            return false;
        }
        
        return true;
    }

    public function getFallbackModel(): ?self
    {
        if ($this->fallback_model) {
            return self::where('slug', $this->fallback_model)->first();
        }
        return null;
    }
}