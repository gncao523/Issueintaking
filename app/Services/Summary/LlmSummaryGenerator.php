<?php

namespace App\Services\Summary;

use App\Models\Issue;
use App\Services\Summary\Contracts\SummaryGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmSummaryGenerator implements SummaryGenerator
{
    public function __construct(
        private readonly SummaryGenerator $fallback,
    ) {}

    public function generate(Issue $issue): SummaryResult
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            return $this->fallback->generate($issue);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => file_get_contents(resource_path('prompts/issue_summary.txt')),
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'title' => $issue->title,
                                'description' => $issue->description,
                                'priority' => $issue->priority->value,
                                'category' => $issue->category,
                                'status' => $issue->status->value,
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                ]);

            if (! $response->successful()) {
                Log::warning('OpenAI summary request failed', ['status' => $response->status()]);

                return $this->fallback->generate($issue);
            }

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return new SummaryResult(
                summary: $parsed['summary'],
                nextAction: $parsed['suggested_next_action'],
            );
        } catch (\Throwable $e) {
            Log::warning('LLM summary generation error', ['message' => $e->getMessage()]);

            return $this->fallback->generate($issue);
        }
    }
}
