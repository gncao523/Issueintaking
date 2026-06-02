<?php

namespace Database\Seeders;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Models\Comment;
use App\Models\Issue;
use App\Services\IssueAttentionService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $attention = app(IssueAttentionService::class);

        $issues = [
            [
                'title' => 'Duplicate charge on March invoice',
                'description' => 'Customer reports a duplicate payment on invoice #8842. Needs refund verification.',
                'priority' => IssuePriority::High,
                'category' => 'billing',
                'status' => IssueStatus::Open,
            ],
            [
                'title' => 'SSO login loop for EU users',
                'description' => 'Users in EU region cannot complete SSO login after password reset.',
                'priority' => IssuePriority::Medium,
                'category' => 'access',
                'status' => IssueStatus::InProgress,
            ],
            [
                'title' => 'API 500 errors on checkout',
                'description' => 'Checkout service returning 500 errors intermittently since 09:00 UTC.',
                'priority' => IssuePriority::High,
                'category' => 'incident',
                'status' => IssueStatus::Open,
            ],
            [
                'title' => 'Update runbook for deploy rollback',
                'description' => 'Documentation request to refresh rollback steps in the deploy runbook.',
                'priority' => IssuePriority::Low,
                'category' => 'general',
                'status' => IssueStatus::Resolved,
            ],
            [
                'title' => 'Warehouse sync delay',
                'description' => 'Inventory sync to warehouse is 45 minutes behind. No customer outage yet.',
                'priority' => IssuePriority::Medium,
                'category' => 'general',
                'status' => IssueStatus::Open,
            ],
        ];

        $created = collect($issues)->map(function (array $data) use ($attention) {
            $issue = new Issue([
                ...$data,
                'priority' => $data['priority']->value,
                'status' => $data['status']->value,
                'summary_status' => SummaryStatus::Ready,
                'summary' => 'Seeded placeholder summary for demo data.',
                'suggested_next_action' => 'Review seeded ticket in the API list endpoint.',
            ]);
            $issue->needs_attention = $attention->shouldFlag($issue);
            $issue->save();

            return $issue;
        });

        Comment::create([
            'issue_id' => $created[0]->id,
            'author_name' => 'Alex Morgan',
            'body' => 'Pulled billing logs — duplicate charge confirmed.',
            'created_at' => now()->subHours(2),
        ]);

        Comment::create([
            'issue_id' => $created[0]->id,
            'author_name' => 'Jamie Lee',
            'body' => 'Waiting on finance approval for refund.',
            'created_at' => now()->subHour(),
        ]);

        Comment::create([
            'issue_id' => $created[1]->id,
            'author_name' => 'Sam Patel',
            'body' => 'Reproduced in staging with EU locale cookie.',
            'created_at' => now()->subHours(3),
        ]);

        Comment::create([
            'issue_id' => $created[2]->id,
            'author_name' => 'On-call Bot',
            'body' => 'Pager triggered. Investigating checkout pods.',
            'created_at' => now()->subMinutes(30),
        ]);

        Comment::create([
            'issue_id' => $created[4]->id,
            'author_name' => 'Riley Chen',
            'body' => 'Sync worker restarted; monitoring lag metric.',
            'created_at' => now()->subMinutes(10),
        ]);
    }
}
