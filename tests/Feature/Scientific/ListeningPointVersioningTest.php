<?php

declare(strict_types=1);

use App\Models\ListeningPoint;
use App\Models\ListeningPointVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('captures immutable public versions for listening points', function () {
    $point = ListeningPoint::factory()->approved()->create([
        'title' => 'Clairiere du matin',
        'exact_latitude' => 46.12345678,
        'exact_longitude' => 2.12345678,
        'public_latitude' => 46.12,
        'public_longitude' => 2.12,
    ]);

    $point->update([
        'title' => 'Clairiere du matin calme',
        'description' => 'Un point preserve pour suivre le reveil sonore.',
    ]);

    $versions = ListeningPointVersion::query()
        ->where('listening_point_id', $point->id)
        ->orderBy('version_number')
        ->get();

    expect($versions)->toHaveCount(2);
    expect($versions[0]->version_number)->toBe(1);
    expect($versions[1]->version_number)->toBe(2);
    expect($versions[1]->parent_version_hash)->toBe($versions[0]->version_hash);
    expect($versions[1]->diff)->toHaveKey('title');
    expect($versions[1]->public_payload)->not->toHaveKey('exact_latitude');
    expect($versions[1]->public_payload)->not->toHaveKey('exact_longitude');
});
