<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\BirdnetDetection;
use App\Models\ListeningPoint;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->service = new AuditLogService;
});

it('logs an approval action', function () {
    $point = ListeningPoint::factory()->create([
        'moderation_status' => 'pending',
    ]);

    $this->service->logApproved($point);

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->action)->toBe('approve');
    expect($log->entity_type)->toBe(ListeningPoint::class);
    expect($log->entity_id)->toBe($point->id);
    expect($log->user_id)->toBe($this->user->id);
    expect($log->old_values)->toBe(['moderation_status' => 'pending']);
    expect($log->new_values)->toBe(['moderation_status' => 'approved']);
    expect($log->ip_address)->not->toBeNull();
});

it('logs a rejection action', function () {
    $point = ListeningPoint::factory()->create([
        'moderation_status' => 'pending',
    ]);

    $this->service->logRejected($point);

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->action)->toBe('reject');
    expect($log->new_values)->toBe(['moderation_status' => 'rejected']);
});

it('logs a validation action for birdnet detection', function () {
    $detection = BirdnetDetection::factory()->create([
        'is_validated' => false,
    ]);

    $this->service->logValidated($detection);

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->action)->toBe('validate');
    expect($log->entity_type)->toBe(BirdnetDetection::class);
    expect($log->new_values)->toBe(['is_validated' => true]);
});

it('logs a merge action', function () {
    $source = ListeningPoint::factory()->create();
    $target = ListeningPoint::factory()->create();

    $this->service->logMerged($source, $target);

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->action)->toBe('merge');
    expect($log->old_values)->toBe(['source_id' => $source->id]);
    expect($log->new_values)->toBe(['target_id' => $target->id]);
});

it('logs a deletion action with full snapshot', function () {
    $point = ListeningPoint::factory()->create([
        'title' => 'Test Point',
    ]);

    $this->service->logDeleted($point);

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->action)->toBe('delete');
    expect($log->old_values)->toHaveKey('title');
    expect($log->old_values['title'])->toBe('Test Point');
    expect($log->new_values)->toBeNull();
});

it('retrieves logs for a specific entity', function () {
    $point = ListeningPoint::factory()->create();
    $this->service->logApproved($point);
    $this->service->logRejected($point);

    $logs = $this->service->getForEntity(ListeningPoint::class, $point->id);

    expect($logs)->toHaveCount(2);
});

it('logs without user when guest', function () {
    auth()->logout();
    $point = ListeningPoint::factory()->create();

    $this->service->logApproved($point);

    expect(AuditLog::first()->user_id)->toBeNull();
});
