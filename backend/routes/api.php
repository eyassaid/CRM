<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoicePdfController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:api');
});

// cache.api: GET responses are cached per user; any successful write clears them (CacheApiResponses).
Route::middleware(['auth:api', 'cache.api'])->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/teams', [AuthController::class, 'teams']);

    Route::apiResource('clients', ClientController::class);

    Route::apiResource('clients.projects', ProjectController::class)->shallow(); // project/1

    Route::apiResource('projects.invoices', InvoiceController::class)->shallow();
    // The invoice PDF, rendered from its items on each request.
    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'show']);

    // The team's people can view it; everything else on teams is admin-only (below).
    Route::apiResource('sections.teams', TeamController::class)->shallow()->only('show');

    // Admins manage every user; team leaders list their members and add employees (see UserPolicy).
    Route::apiResource('users', UserController::class);

    // The signed-in user's notifications: list them and mark them read or unread.
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'update']);

    // Team leader (or admin): clients brought by each member.
    Route::get('/reports/teams/{team}', [TeamReportController::class, 'show']);
});

Route::middleware(['auth:api', 'admin', 'cache.api'])->group(function () {
    Route::apiResource('sections', SectionController::class);
    Route::apiResource('sections.teams', TeamController::class)->shallow()->except('show');

    // Admin: clients and projects per team.
    Route::get('/reports/teams', [TeamReportController::class, 'index']);
});
