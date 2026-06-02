<?php

namespace App\Models;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateIssueSummaryJob;
use App\Services\IssueAttentionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'category',
        'status',
        'summary',
        'suggested_next_action',
        'summary_status',
        'needs_attention',
    ];

    protected function casts(): array
    {
        return [
            'needs_attention' => 'boolean',
            'priority' => IssuePriority::class,
            'status' => IssueStatus::class,
            'summary_status' => SummaryStatus::class,
        ];
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function scopeFilter(Builder $query, ?string $status, ?string $category, ?string $priority): Builder
    {
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        if ($priority !== null && $priority !== '') {
            $query->where('priority', $priority);
        }

        return $query;
    }

    public function recomputeNeedsAttention(): void
    {
        $this->needs_attention = app(IssueAttentionService::class)->shouldFlag($this);
    }

    public function dispatchSummaryGeneration(): void
    {
        $this->update([
            'summary_status' => SummaryStatus::Pending,
            'summary' => null,
            'suggested_next_action' => null,
        ]);

        GenerateIssueSummaryJob::dispatch($this->id);
    }
}
