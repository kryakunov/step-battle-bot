<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/bot', TelegramController::class)->withoutMiddleware(['web', 'csrf'])->name('bot');
Route::get('/set-webhook', [TelegramController::class, 'setWebhook']);
Route::get('/test', [TelegramController::class, 'test']);
