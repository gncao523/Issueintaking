<?php

use App\Http\Controllers\Api\IssueController;
use App\Models\Issue;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::view('/create', 'app');

Route::get('/issues/{issue}', function (Issue $issue) {
    if (request()->expectsJson()) {
        return app(IssueController::class)->show($issue);
    }

    return view('app');
})->whereNumber('issue');
