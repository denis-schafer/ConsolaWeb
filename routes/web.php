<?php

use App\Http\Controllers\EmulatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmulatorController::class, 'index']);
Route::get('/play', [EmulatorController::class, 'play']);
