<?php

declare(strict_types=1);

use App\Models\ListeningPoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies rate limiting to scientific stats api endpoints', function () {
    $response = $this->getJson('/api/scientific-stats/global');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit');
});

it('applies rate limiting to map sound search', function () {
    $response = $this->getJson('/api/map/sounds/search?q=test');
    $response->assertHeader('X-RateLimit-Limit');
});

it('applies rate limiting to listening points pages', function () {
    ListeningPoint::factory()->approved()->count(5)->create();

    $response = $this->get('/listening-points');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit');
});

it('allows requests under the rate limit', function () {
    $response = $this->getJson('/api/scientific-stats/global');
    $response->assertOk();

    $response = $this->get('/listening-points');
    $response->assertOk();
});
