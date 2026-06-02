<?php

namespace App\Jobs;

use App\Enums\SummaryStatus;
use App\Models\Issue;
use App\Services\Summary\Contracts\SummaryGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateIssueSummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $issueId) {}

    public function handle(SummaryGenerator $generator): void
    {
        $issue = Issue::find($this->issueId);

        if ($issue === null) {
            return;
        }

        $result = $generator->generate($issue);

        $issue->update([
            'summary' => $result->summary,
            'suggested_next_action' => $result->nextAction,
            'summary_status' => SummaryStatus::Ready,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Summary generation failed', [
            'issue_id' => $this->issueId,
            'message' => $exception?->getMessage(),
        ]);

        Issue::whereKey($this->issueId)->update([
            'summary_status' => SummaryStatus::Failed,
        ]);
    }
}
