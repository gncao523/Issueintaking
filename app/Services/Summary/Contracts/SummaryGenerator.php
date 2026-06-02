<?php

namespace App\Services\Summary\Contracts;

use App\Models\Issue;
use App\Services\Summary\SummaryResult;

interface SummaryGenerator
{
    public function generate(Issue $issue): SummaryResult;
}
