<?php

declare(strict_types=1);

use App\Models\ListeningPoint;
use App\Services\Scientific\OpenMeteoEnvironmentalDataService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('stores current weather for a listening point from public coordinates only', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-17 12:20:00'));

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'hourly' => [
                'time' => ['2026-05-17T12:00', '2026-05-17T13:00'],
                'temperature_2m' => [18.4, 19.1],
                'relative_humidity_2m' => [64, 61],
                'precipitation' => [0, 0],
                'rain' => [0, 0],
                'snowfall' => [0, 0],
                'weather_code' => [2, 3],
                'wind_speed_10m' => [8.5, 9.2],
                'wind_direction_10m' => [90, 100],
            ],
        ]),
    ]);

    $point = ListeningPoint::factory()->approved()->create([
        'public_latitude' => 46.127891,
        'public_longitude' => 2.124567,
    ]);

    $observation = app(OpenMeteoEnvironmentalDataService::class)->currentForListeningPoint($point);

    expect($observation)->not->toBeNull();
    expect((float) $observation->temperature_c)->toBe(18.4);
    expect($observation->humidity_percent)->toBe(64);
    expect($observation->source)->toBe('open-meteo-current');
    expect($observation->raw_data['privacy'])->toBe('listening_point_public_coordinates_rounded_to_2_decimals');

    Http::assertSent(fn ($request) => $request->data()['latitude'] === 46.13
        && $request->data()['longitude'] === 2.12);

    CarbonImmutable::setTestNow();
});
