<?php

namespace App\Services\Summary;

readonly class SummaryResult
{
    public function __construct(
        public string $summary,
        public string $nextAction,
    ) {}
}
