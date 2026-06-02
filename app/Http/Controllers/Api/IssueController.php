<?php

namespace App\Http\Controllers\Api;

use App\Enums\IssueStatus;
use App\Enums\SummaryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $issues = Issue::query()
            ->filter(
                $request->query('status'),
                $request->query('category'),
                $request->query('priority'),
            )
            ->orderByDesc('created_at')
            ->get();

        return IssueResource::collection($issues);
    }

    public function store(StoreIssueRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? IssueStatus::Open->value;
        $data['summary_status'] = SummaryStatus::Pending->value;

        $issue = new Issue($data);
        $issue->recomputeNeedsAttention();
        $issue->save();

        $issue->dispatchSummaryGeneration();

        return (new IssueResource($issue->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Issue $issue): IssueResource
    {
        $issue->load('comments');

        return new IssueResource($issue);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): IssueResource
    {
        $validated = $request->validated();
        $descriptionChanged = array_key_exists('description', $validated)
            && $validated['description'] !== $issue->description;

        $issue->fill($validated);

        if (array_key_exists('priority', $validated)) {
            $issue->recomputeNeedsAttention();
        }

        $issue->save();

        if ($descriptionChanged) {
            $issue->dispatchSummaryGeneration();
        }

        return new IssueResource($issue->fresh());
    }
}
