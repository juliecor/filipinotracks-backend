<?php

use App\Http\Controllers\PublicPropertyPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Server-rendered share page — injects Open Graph meta tags so links pasted
// in Messenger / Viber / Facebook / Slack / Discord show a rich preview with
// the property's satellite-map thumbnail.
Route::get('/p/{code}', [PublicPropertyPageController::class, 'show'])
    ->where('code', '[A-Za-z0-9\-_]+');
