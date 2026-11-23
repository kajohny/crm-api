<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::post('/companies', [CompanyController::class, 'store']);
Route::get('/companies', [CompanyController::class, 'index']);

Route::post('/calls', [CallController::class, 'store']);