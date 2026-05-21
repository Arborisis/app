<?php

declare(strict_types=1);

use App\Models\Stat;
use App\Services\Stats\StatsCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(StatsCacheService::class);
});

it('computes and stores a stat', function () {
    $value = $this->service->get('global');

    expect($value)->toBeArray();
    expect(Stat::where('key', 'global')->exists())->toBeTrue();
});

it('returns cached stat without recomputing', function () {
    $this->service->get('global');
    $firstCalculated = Stat::where('key', 'global')->value('calculated_at');

    $this->service->get('global');
    $secondCalculated = Stat::where('key', 'global')->value('calculated_at');

    expect($firstCalculated)->toEqual($secondCalculated);
});

it('refreshes a specific stat', function () {
    $this->service->get('global');
    $firstCalculated = Stat::where('key', 'global')->value('calculated_at');

    sleep(1);
    $this->service->refresh('global');
    $secondCalculated = Stat::where('key', 'global')->value('calculated_at');

    expect($secondCalculated)->toBeGreaterThan($firstCalculated);
});

it('gets multiple stats at once', function () {
    $result = $this->service->getMany(['global', 'listening_points']);

    expect($result)->toHaveKeys(['global', 'listening_points']);
    expect($result['global'])->toBeArray();
    expect($result['listening_points'])->toBeArray();
});

it('detects stale stats', function () {
    $stat = Stat::create([
        'key' => 'test_stale',
        'value' => ['test' => true],
        'category' => 'test',
        'calculated_at' => now()->subHours(2),
    ]);

    expect($stat->isStale(30))->toBeTrue();
    expect($stat->isStale(180))->toBeFalse();
});

it('clears all stats', function () {
    $this->service->get('global');
    expect(Stat::count())->toBeGreaterThan(0);

    $this->service->clear();
    expect(Stat::count())->toBe(0);
});
