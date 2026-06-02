<?php

namespace App\Services\Summary;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Services\Summary\Contracts\SummaryGenerator;
use Illuminate\Support\Str;

class RulesBasedSummaryGenerator implements SummaryGenerator
{
    public function generate(Issue $issue): SummaryResult
    {
        $category = Str::lower($issue->category);
        $description = Str::lower($issue->description);
        $priorityLabel = $issue->priority->value;

        $topic = $this->detectTopic($category, $description);
        $summary = sprintf(
            '%s priority %s issue in %s: %s.',
            ucfirst($priorityLabel),
            $issue->status->value,
            $issue->category,
            $topic,
        );

        $nextAction = $this->suggestNextAction($issue, $category, $description);

        return new SummaryResult($summary, $nextAction);
    }

    private function detectTopic(string $category, string $description): string
    {
        if (Str::contains($description, ['payment', 'invoice', 'charge', 'refund']) || $category === 'billing') {
            return 'customer billing or payment needs review';
        }

        if (Str::contains($description, ['login', 'password', 'auth', 'sso']) || $category === 'access') {
            return 'user access or authentication is affected';
        }

        if (Str::contains($description, ['down', 'outage', '500', 'error']) || $category === 'incident') {
            return 'service reliability incident reported';
        }

        return 'general operational request captured for triage';
    }

    private function suggestNextAction(Issue $issue, string $category, string $description): string
    {
        if ($issue->priority === IssuePriority::High && $issue->status === IssueStatus::Open) {
            return 'Assign an on-call engineer and acknowledge the ticket within 15 minutes.';
        }

        if (Str::contains($description, ['refund', 'chargeback']) || $category === 'billing') {
            return 'Verify the latest invoice in billing and reply with payment status.';
        }

        if ($issue->status === IssueStatus::InProgress) {
            return 'Post a progress update comment and confirm the next checkpoint time.';
        }

        if ($issue->status === IssueStatus::Resolved) {
            return 'Send closure confirmation to the requester and archive related alerts.';
        }

        return 'Review the description, set status to in_progress, and add an owner comment.';
    }
}
