<?php

use App\Http\Controllers\TelegramController;
use App\Models\Step;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/bot', TelegramController::class)->withoutMiddleware(['web', 'csrf'])->name('bot');
Route::get('/set-webhook', [TelegramController::class, 'setWebhook']);

