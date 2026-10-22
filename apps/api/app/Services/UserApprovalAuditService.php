<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserApprovalAudit;

class UserApprovalAuditService
{
    public function record(User $target, ?User $actor, string $action, array $metadata = []): void
    {
        UserApprovalAudit::create([
            'target_user_id' => $target->id,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'metadata' => $metadata ?: null,
        ]);
    }
}
