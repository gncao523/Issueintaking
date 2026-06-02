<?php

use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\IssueController;
use Illuminate\Support\Facades\Route;

Route::get('/issues', [IssueController::class, 'index']);
Route::post('/issues', [IssueController::class, 'store']);
Route::patch('/issues/{issue}', [IssueController::class, 'update']);
Route::post('/issues/{issue}/comments', [CommentController::class, 'store']);
