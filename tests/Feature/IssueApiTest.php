<?php

namespace Tests\Feature;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateIssueSummaryJob;
use App\Models\Comment;
use App\Models\Issue;
use App\Services\Summary\Contracts\SummaryGenerator;
use App\Services\Summary\SummaryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IssueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_can_be_created_successfully(): void
    {
        Queue::fake();

        $response = $this->postJson('/issues', [
            'title' => 'Payment failure',
            'description' => 'Customer cannot complete checkout payment.',
            'priority' => 'medium',
            'category' => 'billing',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.summary_status', 'pending')
            ->assertJsonPath('data.needs_attention', false);

        $this->assertDatabaseHas('issues', [
            'title' => 'Payment failure',
            'summary_status' => 'pending',
        ]);
    }

    public function test_issue_create_validation_failure(): void
    {
        $response = $this->postJson('/issues', [
            'title' => '   ',
            'description' => '',
            'priority' => 'urgent',
            'category' => 'billing',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['title', 'description', 'priority']);
    }

    public function test_list_issues_with_combined_filters(): void
    {
        Issue::factory()->create([
            'status' => IssueStatus::Open->value,
            'category' => 'billing',
            'priority' => IssuePriority::High->value,
            'needs_attention' => true,
        ]);

        Issue::factory()->create([
            'status' => IssueStatus::Open->value,
            'category' => 'access',
            'priority' => IssuePriority::High->value,
        ]);

        Issue::factory()->create([
            'status' => IssueStatus::Resolved->value,
            'category' => 'billing',
            'priority' => IssuePriority::High->value,
        ]);

        $response = $this->getJson('/issues?status=open&priority=high&category=billing');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('billing', $response->json('data.0.category'));
    }

    public function test_comment_can_be_added_to_issue(): void
    {
        $issue = Issue::factory()->create();

        $response = $this->postJson("/issues/{$issue->id}/comments", [
            'author_name' => 'Taylor Kim',
            'body' => 'Investigating logs now.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.author_name', 'Taylor Kim');

        $this->assertDatabaseHas('comments', [
            'issue_id' => $issue->id,
            'body' => 'Investigating logs now.',
        ]);
    }

    public function test_show_issue_eager_loads_comments_without_n_plus_one(): void
    {
        $issue = Issue::factory()->create();
        Comment::factory()->count(3)->create(['issue_id' => $issue->id]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->getJson("/issues/{$issue->id}");

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()
            ->assertJsonCount(3, 'data.comments');

        $this->assertLessThanOrEqual(3, count($queries), 'Expected eager loading (issue + comments), not N+1.');
    }

    public function test_creating_issue_dispatches_summary_job(): void
    {
        Queue::fake();

        $this->postJson('/issues', [
            'title' => 'New outage',
            'description' => 'Service is down in us-east-1.',
            'priority' => 'high',
            'category' => 'incident',
        ])->assertCreated();

        Queue::assertPushed(GenerateIssueSummaryJob::class);
    }

    public function test_summary_job_populates_fields(): void
    {
        $issue = Issue::factory()->create([
            'summary_status' => SummaryStatus::Pending->value,
        ]);

        $this->mock(SummaryGenerator::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn(new SummaryResult(
                    'High priority incident in us-east-1.',
                    'Page on-call and open a status incident channel.',
                ));
        });

        (new GenerateIssueSummaryJob($issue->id))->handle(app(SummaryGenerator::class));

        $issue->refresh();

        $this->assertSame(SummaryStatus::Ready, $issue->summary_status);
        $this->assertSame('High priority incident in us-east-1.', $issue->summary);
        $this->assertSame('Page on-call and open a status incident channel.', $issue->suggested_next_action);
    }

    public function test_high_priority_issue_sets_needs_attention(): void
    {
        Queue::fake();

        $this->postJson('/issues', [
            'title' => 'Critical',
            'description' => 'Production down.',
            'priority' => 'high',
            'category' => 'incident',
        ])->assertCreated()
            ->assertJsonPath('data.needs_attention', true);
    }

    public function test_updating_description_re_dispatches_summary_job(): void
    {
        Queue::fake();

        $issue = Issue::factory()->create([
            'description' => 'Original text',
            'summary_status' => SummaryStatus::Ready->value,
        ]);

        $this->patchJson("/issues/{$issue->id}", [
            'description' => 'Updated text with new details',
        ])->assertOk()
            ->assertJsonPath('data.summary_status', 'pending');

        Queue::assertPushed(GenerateIssueSummaryJob::class);
    }

    public function test_updating_status_only_does_not_dispatch_summary_job(): void
    {
        Queue::fake();

        $issue = Issue::factory()->create([
            'summary_status' => SummaryStatus::Ready->value,
        ]);

        $this->patchJson("/issues/{$issue->id}", [
            'status' => 'in_progress',
        ])->assertOk();

        Queue::assertNothingPushed();
    }
}
