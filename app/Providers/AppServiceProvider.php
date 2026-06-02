<?php

namespace App\Providers;

use App\Services\Summary\Contracts\SummaryGenerator;
use App\Services\Summary\LlmSummaryGenerator;
use App\Services\Summary\RulesBasedSummaryGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SummaryGenerator::class, function () {
            $rules = new RulesBasedSummaryGenerator;

            return new LlmSummaryGenerator($rules);
        });
    }

    public function boot(): void
    {
        //
    }
}
