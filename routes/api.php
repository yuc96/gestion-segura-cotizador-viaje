<?php

use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\PricingController;
use App\Http\Controllers\Api\QuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/pricing', [PricingController::class, 'show']);

Route::get('/quotes', [QuoteController::class, 'index']);
Route::post('/quotes', [QuoteController::class, 'store']);
Route::get('/quotes/{quote}', [QuoteController::class, 'show']);
Route::post('/quotes/{quote}/contract', [QuoteController::class, 'contract']);
Route::get('/quotes/{quote}/pdf', [QuoteController::class, 'pdf']);
