<?php

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    protected $model = Issue::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(IssuePriority::values()),
            'category' => fake()->randomElement(['billing', 'access', 'incident', 'general']),
            'status' => IssueStatus::Open->value,
            'summary' => null,
            'suggested_next_action' => null,
            'summary_status' => SummaryStatus::Pending->value,
            'needs_attention' => false,
        ];
    }

    public function highPriority(): static
    {
        return $this->state(fn () => [
            'priority' => IssuePriority::High->value,
            'needs_attention' => true,
        ]);
    }
}
