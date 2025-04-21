<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BoxController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post("/login", [AuthController::class, 'login']);
Route::post("/logout", [AuthController::class, 'logout']);
Route::post("/refresh", [AuthController::class, 'refresh'])->middleware("auth:api");

Route::get("/boxes", [BoxController::class, 'index']);
Route::post("/boxes", [BoxController::class, 'store']);
Route::get("/boxes/{box}", [BoxController::class, 'show']);



Route::post("user/update-location", [UserController::class, 'updateLocation'])
    ->middleware('auth:api');


