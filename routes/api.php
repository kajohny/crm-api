<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::post('/companies', [CompanyController::class, 'store']);
Route::get('/companies', [CompanyController::class, 'index']);

Route::post('/calls', [CallController::class, 'store']);

Route::post('/meetings', [MeetingController::class, 'store']);
Route::patch('/meetings/{id}', [MeetingController::class, 'update']);

Route::get('/managers/{id}/schedule', [ManagerController::class, 'schedule']);