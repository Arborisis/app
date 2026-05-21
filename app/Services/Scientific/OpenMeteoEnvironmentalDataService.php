<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Enums\Season;
use App\Enums\TimeOfDay;
use App\Models\EnvironmentalObservation;
use App\Models\ListeningPoint;
use App\Models\Sound;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoEnvironmentalDataService
{
    /**
     * Enrich a public sound with free Open-Meteo weather data.
     *
     * Coordinates sent to Open-Meteo are the public/obfuscated coordinates only.
     */
    public function enrichSound(Sound $sound, bool $force = false): ?EnvironmentalObservation
    {
        if (! (bool) config('services.open_meteo.enabled', true)) {
            return $sound->environmentalObservation;
        }

        $sound->loadMissing(['soundLocation', 'environmentalObservation']);

        if (! $sound->recorded_at || ! $sound->soundLocation) {
            return $sound->environmentalObservation;
        }

        $latitude = $sound->soundLocation->public_latitude;
        $longitude = $sound->soundLocation->public_longitude;

        if ($latitude === null || $longitude === null) {
            return $sound->environmentalObservation;
        }

        $existing = $sound->environmentalObservation;
        if (! $force && $existing && str_contains((string) $existing->source, 'open-meteo')) {
            return $existing;
        }

        $recordedAt = $sound->recorded_at;
        $weather = $this->fetchWeather((float) $latitude, (float) $longitude, $recordedAt, ['sound_id' => $sound->id]);
        $hourly = $weather['hourly'];

        if ($hourly === []) {
            return $existing;
        }

        $source = $existing && $existing->source === 'user' ? 'user+open-meteo' : 'open-meteo';
        $condition = $this->weatherCondition((int) ($hourly['weather_code'] ?? -1));

        return EnvironmentalObservation::updateOrCreate(
            ['sound_id' => $sound->id],
            [
                'listening_point_id' => $sound->listening_point_id,
                'season' => Season::fromDate($recordedAt)->value,
                'time_of_day' => TimeOfDay::fromHour((int) $recordedAt->format('H'))->value,
                'temperature_c' => $this->nullableFloat($hourly['temperature_2m'] ?? null),
                'humidity_percent' => $this->nullableInt($hourly['relative_humidity_2m'] ?? null),
                'wind_speed_kmh' => $this->nullableFloat($hourly['wind_speed_10m'] ?? null),
                'wind_direction' => $this->cardinalDirection($this->nullableFloat($hourly['wind_direction_10m'] ?? null)),
                'is_raining' => (($this->nullableFloat($hourly['rain'] ?? null) ?? 0.0) > 0.0)
                    || (($this->nullableFloat($hourly['precipitation'] ?? null) ?? 0.0) > 0.0 && ! $this->isSnowCode((int) ($hourly['weather_code'] ?? -1))),
                'is_snowing' => (($this->nullableFloat($hourly['snowfall'] ?? null) ?? 0.0) > 0.0)
                    || $this->isSnowCode((int) ($hourly['weather_code'] ?? -1)),
                'weather_condition' => $condition,
                'source' => $source,
                'raw_data' => [
                    'provider' => 'open-meteo',
                    'endpoint' => $weather['endpoint'],
                    'privacy' => 'public_coordinates_rounded_to_2_decimals',
                    'observed_at' => $hourly['time'] ?? null,
                    'hourly' => $hourly,
                ],
            ]
        );
    }

    /**
     * Enrich an approved public listening point with Open-Meteo data.
     *
     * This uses only the point's public coordinates. The observation is stored
     * without a sound_id so it can describe the point itself.
     */
    public function enrichListeningPoint(ListeningPoint $point, ?CarbonInterface $observedAt = null, bool $force = false): ?EnvironmentalObservation
    {
        if (! (bool) config('services.open_meteo.enabled', true)) {
            return $point->environmentalObservations()->whereNull('sound_id')->latest()->first();
        }

        if ($point->public_latitude === null || $point->public_longitude === null) {
            return $point->environmentalObservations()->whereNull('sound_id')->latest()->first();
        }

        $existing = $point->environmentalObservations()
            ->whereNull('sound_id')
            ->where('source', 'like', '%open-meteo%')
            ->latest()
            ->first();

        if (! $force && $existing) {
            return $existing;
        }

        $observedAt ??= $point->last_recorded_at
            ?? $point->first_recorded_at
            ?? $point->approved_at
            ?? now();

        $weather = $this->fetchWeather(
            (float) $point->public_latitude,
            (float) $point->public_longitude,
            $observedAt,
            ['listening_point_id' => $point->id]
        );
        $hourly = $weather['hourly'];

        if ($hourly === []) {
            return $existing;
        }

        $condition = $this->weatherCondition((int) ($hourly['weather_code'] ?? -1));

        return EnvironmentalObservation::updateOrCreate(
            [
                'sound_id' => null,
                'listening_point_id' => $point->id,
            ],
            [
                'season' => Season::fromDate($observedAt)->value,
                'time_of_day' => TimeOfDay::fromHour((int) $observedAt->format('H'))->value,
                'temperature_c' => $this->nullableFloat($hourly['temperature_2m'] ?? null),
                'humidity_percent' => $this->nullableInt($hourly['relative_humidity_2m'] ?? null),
                'wind_speed_kmh' => $this->nullableFloat($hourly['wind_speed_10m'] ?? null),
                'wind_direction' => $this->cardinalDirection($this->nullableFloat($hourly['wind_direction_10m'] ?? null)),
                'is_raining' => (($this->nullableFloat($hourly['rain'] ?? null) ?? 0.0) > 0.0)
                    || (($this->nullableFloat($hourly['precipitation'] ?? null) ?? 0.0) > 0.0 && ! $this->isSnowCode((int) ($hourly['weather_code'] ?? -1))),
                'is_snowing' => (($this->nullableFloat($hourly['snowfall'] ?? null) ?? 0.0) > 0.0)
                    || $this->isSnowCode((int) ($hourly['weather_code'] ?? -1)),
                'weather_condition' => $condition,
                'source' => 'open-meteo-point',
                'raw_data' => [
                    'provider' => 'open-meteo',
                    'endpoint' => $weather['endpoint'],
                    'privacy' => 'listening_point_public_coordinates_rounded_to_2_decimals',
                    'observed_at' => $hourly['time'] ?? null,
                    'hourly' => $hourly,
                ],
            ]
        );
    }

    /**
     * Return near real-time weather for a listening point using public coordinates only.
     */
    public function currentForListeningPoint(ListeningPoint $point, bool $force = false): ?EnvironmentalObservation
    {
        if (! (bool) config('services.open_meteo.enabled', true)) {
            return $point->environmentalObservations()->whereNull('sound_id')->latest()->first();
        }

        if ($point->public_latitude === null || $point->public_longitude === null) {
            return $point->environmentalObservations()->whereNull('sound_id')->latest()->first();
        }

        $existing = $point->environmentalObservations()
            ->whereNull('sound_id')
            ->where('source', 'open-meteo-current')
            ->latest()
            ->first();

        if (! $force && $existing && $this->isCurrentObservationFresh($existing)) {
            return $existing;
        }

        $observedAt = now();
        $weather = $this->fetchWeather(
            (float) $point->public_latitude,
            (float) $point->public_longitude,
            $observedAt,
            ['listening_point_id' => $point->id, 'mode' => 'current']
        );
        $hourly = $weather['hourly'];

        if ($hourly === []) {
            return $existing;
        }

        $condition = $this->weatherCondition((int) ($hourly['weather_code'] ?? -1));

        return EnvironmentalObservation::updateOrCreate(
            [
                'sound_id' => null,
                'listening_point_id' => $point->id,
            ],
            [
                'season' => Season::fromDate($observedAt)->value,
                'time_of_day' => TimeOfDay::fromHour((int) $observedAt->format('H'))->value,
                'temperature_c' => $this->nullableFloat($hourly['temperature_2m'] ?? null),
                'humidity_percent' => $this->nullableInt($hourly['relative_humidity_2m'] ?? null),
                'wind_speed_kmh' => $this->nullableFloat($hourly['wind_speed_10m'] ?? null),
                'wind_direction' => $this->cardinalDirection($this->nullableFloat($hourly['wind_direction_10m'] ?? null)),
                'is_raining' => (($this->nullableFloat($hourly['rain'] ?? null) ?? 0.0) > 0.0)
                    || (($this->nullableFloat($hourly['precipitation'] ?? null) ?? 0.0) > 0.0 && ! $this->isSnowCode((int) ($hourly['weather_code'] ?? -1))),
                'is_snowing' => (($this->nullableFloat($hourly['snowfall'] ?? null) ?? 0.0) > 0.0)
                    || $this->isSnowCode((int) ($hourly['weather_code'] ?? -1)),
                'weather_condition' => $condition,
                'source' => 'open-meteo-current',
                'raw_data' => [
                    'provider' => 'open-meteo',
                    'endpoint' => $weather['endpoint'],
                    'privacy' => 'listening_point_public_coordinates_rounded_to_2_decimals',
                    'observed_at' => $hourly['time'] ?? null,
                    'fetched_at' => $observedAt->toIso8601String(),
                    'hourly' => $hourly,
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $logContext
     *
     * @return array{endpoint: string|null, hourly: array<string, mixed>}
     */
    private function fetchWeather(float $latitude, float $longitude, CarbonInterface $observedAt, array $logContext = []): array
    {
        $date = $observedAt->toDateString();
        $endpoint = $observedAt->isBefore(now()->startOfDay())
            ? (string) config('services.open_meteo.archive_url')
            : (string) config('services.open_meteo.forecast_url');

        $response = Http::timeout((int) config('services.open_meteo.timeout', 8))
            ->acceptJson()
            ->get($endpoint, [
                'latitude' => round($latitude, 2),
                'longitude' => round($longitude, 2),
                'start_date' => $date,
                'end_date' => $date,
                'hourly' => implode(',', [
                    'temperature_2m',
                    'relative_humidity_2m',
                    'precipitation',
                    'rain',
                    'snowfall',
                    'weather_code',
                    'wind_speed_10m',
                    'wind_direction_10m',
                ]),
                'timezone' => 'auto',
            ]);

        if (! $response->ok()) {
            Log::warning('Open-Meteo enrichment failed.', $logContext + [
                'status' => $response->status(),
            ]);

            return ['endpoint' => $endpoint, 'hourly' => []];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return ['endpoint' => $endpoint, 'hourly' => []];
        }

        return [
            'endpoint' => $endpoint,
            'hourly' => $this->closestHourlyObservation($payload, $observedAt),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function closestHourlyObservation(array $payload, CarbonInterface $recordedAt): array
    {
        $hourly = $payload['hourly'] ?? null;
        if (! is_array($hourly) || ! isset($hourly['time']) || ! is_array($hourly['time'])) {
            return [];
        }

        $closestIndex = null;
        $closestDelta = PHP_INT_MAX;

        foreach ($hourly['time'] as $index => $time) {
            if (! is_string($time)) {
                continue;
            }

            $delta = abs($recordedAt->diffInSeconds(CarbonImmutable::parse($time), absolute: false));
            if ($delta < $closestDelta) {
                $closestDelta = $delta;
                $closestIndex = $index;
            }
        }

        if ($closestIndex === null) {
            return [];
        }

        $observation = [];
        foreach ($hourly as $key => $values) {
            if (is_array($values) && array_key_exists($closestIndex, $values)) {
                $observation[$key] = $values[$closestIndex];
            }
        }

        return $observation;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function cardinalDirection(?float $degrees): ?string
    {
        if ($degrees === null) {
            return null;
        }

        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];

        return $directions[((int) round($degrees / 45)) % 8];
    }

    private function weatherCondition(int $code): ?string
    {
        return match (true) {
            $code === 0 => 'clear',
            in_array($code, [1, 2, 3], true) => 'cloudy',
            in_array($code, [45, 48], true) => 'fog',
            in_array($code, [51, 53, 55, 56, 57], true) => 'drizzle',
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => 'rain',
            in_array($code, [71, 73, 75, 77, 85, 86], true) => 'snow',
            in_array($code, [95, 96, 99], true) => 'storm',
            default => null,
        };
    }

    private function isSnowCode(int $code): bool
    {
        return in_array($code, [71, 73, 75, 77, 85, 86], true);
    }

    private function isCurrentObservationFresh(EnvironmentalObservation $observation): bool
    {
        $refreshMinutes = (int) config('services.open_meteo.current_refresh_minutes', 15);

        return $observation->updated_at?->greaterThan(now()->subMinutes($refreshMinutes)) ?? false;
    }
}
