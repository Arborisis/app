<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property array<string, mixed>|null $value
 * @property StatCategory $category
 * @property Carbon $calculated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|Stat newModelQuery()
 * @method static Builder<static>|Stat newQuery()
 * @method static Builder<static>|Stat query()
 * @method static Builder<static>|Stat category(StatCategory|string $category)
 * @method static Builder<static>|Stat key(string $key)
 * @method static Builder<static>|Stat stale(int $ttlMinutes = 60)
 * @method static Builder<static>|Stat fresh(int $ttlMinutes = 60)
 */
class Stat extends Model
{
    /** @use HasFactory<\Database\Factories\StatFactory> */
    use HasFactory;

    protected $table = 'stats';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'category',
        'calculated_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'array',
        'category' => StatCategory::class,
        'calculated_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope by category.
     */
    public function scopeCategory(Builder $query, StatCategory|string $category): Builder
    {
        $value = $category instanceof StatCategory ? $category->value : $category;

        return $query->where('category', $value);
    }

    /**
     * Scope by exact key.
     */
    public function scopeKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope to stats that are stale (older than N minutes).
     */
    public function scopeStale(Builder $query, int $ttlMinutes = 60): Builder
    {
        return $query->where(
            'calculated_at',
            '<',
            now()->subMinutes($ttlMinutes)
        );
    }

    /**
     * Scope to stats that are still fresh (calculated within N minutes).
     */
    public function scopeFresh(Builder $query, int $ttlMinutes = 60): Builder
    {
        return $query->where(
            'calculated_at',
            '>=',
            now()->subMinutes($ttlMinutes)
        );
    }

    // -------------------------------------------------------------------------
    // Accessors / Helpers
    // -------------------------------------------------------------------------

    /**
     * Determine if the stat is stale based on a TTL.
     */
    public function isStale(int $ttlMinutes = 60): bool
    {
        if ($this->calculated_at === null) {
            return true;
        }

        return $this->calculated_at->diffInMinutes(now(), absolute: false) > $ttlMinutes;
    }

    /**
     * Determine if the stat is still fresh.
     */
    public function isFresh(int $ttlMinutes = 60): bool
    {
        return ! $this->isStale($ttlMinutes);
    }

    /**
     * Touch the calculated_at timestamp to now.
     */
    public function touchCalculatedAt(): bool
    {
        return $this->update(['calculated_at' => now()]);
    }

    /**
     * Get a scalar numeric value safely.
     */
    public function getNumericValue(): float|int|null
    {
        if (is_numeric($this->value)) {
            return is_float($this->value + 0) ? (float) $this->value : (int) $this->value;
        }

        return null;
    }

    /**
     * Get a scalar string value safely.
     */
    public function getStringValue(): ?string
    {
        if (is_string($this->value)) {
            return $this->value;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the value of a stat by its key.
     *
     * @return array<string, mixed>|null
     */
    public static function getValue(string $key, mixed $default = null): ?array
    {
        $stat = static::key($key)->first();

        return $stat?->value ?? $default;
    }

    /**
     * Store or update a stat by key.
     *
     * @param array<string, mixed>|string|int|float $value
     */
    public static function put(
        string $key,
        array|string|int|float $value,
        ?StatCategory $category = null,
        ?\DateTimeInterface $calculatedAt = null,
    ): self {
        /** @var self $stat */
        $stat = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'category' => $category ?? StatCategory::General,
                'calculated_at' => $calculatedAt ?? now(),
            ]
        );

        return $stat;
    }

    /**
     * Delete every cached stat row.
     */
    public static function clearAll(): int
    {
        return static::query()->delete();
    }
}
