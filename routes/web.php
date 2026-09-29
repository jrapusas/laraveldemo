<?php

use App\Http\LegacyPublicFileResponse;
use App\Http\LegacyScriptResponse;
use Illuminate\Support\Facades\Route;

Route::view('/portfolio', 'portfolio');

Route::get('/legacy/ticket_summary.php', LegacyScriptResponse::route('ticket_summary.php'));
Route::get('/legacy/{path}', LegacyPublicFileResponse::route())->where('path', '.*');

Route::view('/{any?}', 'app')->where('any', '^(?!legacy/).*');
