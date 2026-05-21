<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function log(
        string $action,
        Model $entity,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
    ): void {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entity::class,
            'entity_id' => $entity->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
        ]);
    }

    public function logValidated(Model $entity, ?string $reason = null): void
    {
        $this->log('validate', $entity, null, ['is_validated' => true], $reason);
    }

    public function logApproved(Model $entity, ?string $reason = null): void
    {
        $this->log('approve', $entity, ['moderation_status' => 'pending'], ['moderation_status' => 'approved'], $reason);
    }

    public function logRejected(Model $entity, ?string $reason = null): void
    {
        $this->log('reject', $entity, ['moderation_status' => 'pending'], ['moderation_status' => 'rejected'], $reason);
    }

    public function logMerged(Model $source, Model $target, ?string $reason = null): void
    {
        $this->log('merge', $target, ['source_id' => $source->id], ['target_id' => $target->id], $reason);
    }

    public function logDeleted(Model $entity, ?string $reason = null): void
    {
        $this->log('delete', $entity, $entity->toArray(), null, $reason);
    }

    public function getForEntity(string $entityType, int $entityId)
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get();
    }
}
