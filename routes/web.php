<?php

use App\Http\Controllers\OwnerLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/owner/login', OwnerLoginController::class)
    ->middleware('throttle:owner-login');
