<?php

namespace App\Services;

use App\Enums\IssuePriority;
use App\Models\Issue;

class IssueAttentionService
{
    /**
     * needs_attention is true when priority is high.
     * Recomputed on create and whenever priority is updated via PATCH.
     */
    public function shouldFlag(Issue $issue): bool
    {
        return $issue->priority === IssuePriority::High;
    }
}
