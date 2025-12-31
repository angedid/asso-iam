<?php

use App\Http\Controllers\GuestApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('test', [GuestApiController::class, 'test']);
