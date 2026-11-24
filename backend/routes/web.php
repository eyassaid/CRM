<?php

use Illuminate\Support\Facades\Route;

// The UI lives in ../frontend (Vue SPA). The backend only serves the API under /api.
Route::get('/', function () {
    return response()->json(['name' => config('app.name'), 'api' => url('/api')]);
});
