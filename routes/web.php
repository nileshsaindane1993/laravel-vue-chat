<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request) => redirect()->route(
    $request->user() ? 'chat.index' : 'login',
))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/users/{user}/conversation', [ChatController::class, 'start'])
        ->name('chat.conversations.store');
    Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [ChatController::class, 'send'])
        ->name('chat.messages.store');
    Route::post('/chat/{conversation}/read', [ChatController::class, 'markRead'])
        ->name('chat.read.store');
});
