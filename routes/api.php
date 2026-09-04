<?php

use App\Http\Middleware\AuthenticateApiTokenIfPresent;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(AuthenticateApiTokenIfPresent::class)
    ->group(__DIR__.'/public_api.php');

Route::middleware('auth:sanctum')
    ->group(__DIR__.'/authenticated_api.php');
