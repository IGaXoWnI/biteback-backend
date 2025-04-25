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
Route::get("/merchant/offers/getAll", [BoxController::class, 'getAll'])->middleware('auth:api')->middleware("business");
Route::post("/merchant/offers", [BoxController::class, 'store'])->middleware('auth:api')->middleware("business");
Route::get("/boxes/{box}", [BoxController::class, 'show']);
Route::put("/merchant/updateOffer/{box}", [BoxController::class, 'update'])->middleware('auth:api')->middleware("business");
Route::delete("/merchant/deleteOffer/{box}", [BoxController::class, 'destroy'])->middleware('auth:api')->middleware("business");
Route::put("/merchant/updateOfferStatus/{box}", [BoxController::class, 'updateStatus'])->middleware('auth:api')->middleware("business");



Route::post("user/update-location", [UserController::class, 'updateLocation'])
    ->middleware('auth:api');



Route::get("/getAllZone", [BoxController::class, 'getAllZone'])->middleware('auth:api');
