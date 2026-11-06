<?php

use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [SessionController::class, 'store'])->name('login');
Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth');

Route::view('/', 'app');
Route::view('/{any}', 'app')->where('any', '^(?!api(?:/|$)|sanctum(?:/|$)).*$');
