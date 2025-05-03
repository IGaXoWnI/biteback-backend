<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BoxController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ReservationController;

// Move this line BEFORE your other box routes to prevent conflicts
Route::get('/search-boxes', [BoxController::class, 'searchBoxes'])->middleware('auth:api');

Route::post('/register', [AuthController::class, 'register']);
Route::post("/login", [AuthController::class, 'login']);
Route::post("/logout", [AuthController::class, 'logout']);
Route::post("/refresh", [AuthController::class, 'refresh'])->middleware("auth:api");

Route::get("/boxes", [BoxController::class, 'index']);
Route::get("/merchant/offers/getAll", [BoxController::class, 'getAll'])->middleware('auth:api')->middleware("business");
Route::get("/getAllBusinessBoxes", [BoxController::class, 'getBusinessBoxes'])->middleware('auth:api')->middleware("business");
Route::post("/merchant/offers", [BoxController::class, 'storeBox'])->middleware('auth:api')->middleware("business");
Route::get("/boxes/{box}", [BoxController::class, 'showSpecifiqueBox']);
Route::put("/merchant/updateOffer/{box}", [BoxController::class, 'updateBox'])->middleware('auth:api')->middleware("business");
Route::delete("/merchant/deleteOffer/{box}", [BoxController::class, 'destroy'])->middleware('auth:api')->middleware("business");
Route::put("/merchant/updateOfferStatus/{box}", [BoxController::class, 'updateStatus'])->middleware('auth:api')->middleware("business");


Route::get("/businesses", [BusinessController::class, 'getAllBusinesses'])->middleware('auth:api')->middleware("admin");
Route::delete("/businesses/{business}", [BusinessController::class, 'deleteBusiness'])->middleware('auth:api')->middleware("admin");
Route::get("/users", [UserController::class, 'getAllConsumers'])->middleware('auth:api')->middleware("admin");
Route::delete("/users/{user}", [UserController::class, 'deleteUser'])->middleware('auth:api')->middleware("admin");
Route::get("/getAllBoxes", [BoxController::class, 'getAllBoxes'])->middleware('auth:api')->middleware("admin");
Route::delete("/boxes/{box}", [BoxController::class, 'deleteBox'])->middleware('auth:api')->middleware("admin");
Route::post("/changeStatus/{user}", [UserController::class, 'updateUserStatus'])->middleware('auth:api')->middleware("admin");


Route::post("user/update-location", [UserController::class, 'updateUserLocation'])
    ->middleware('auth:api');
Route::post("user/get-location", [UserController::class, 'getLocation'])->middleware('auth:api');



Route::get("/near-me", [BoxController::class, 'getAllZone'])->middleware('auth:api');
Route::get("/getAvgRating/{box_id}", [BoxController::class, 'getBoxRating'])->middleware('auth:api');



Route::post('/reports', [ReportController::class, 'submitReport'])->middleware('auth:api');
Route::get('/reports', [ReportController::class, 'getAllReports'])->middleware('auth:api')->middleware("admin");
Route::put('/reports/{id}', [ReportController::class, 'updateReportStatus'])->middleware('auth:api')->middleware("admin");



Route::middleware('auth:api')->group(function () {
    Route::post('/makeReservation', [ReservationController::class, 'makeReservation']);

    Route::get('/reservations/user', [ReservationController::class, 'getUserReservations']);

    Route::patch('/reservations/{id}/status', [ReservationController::class, 'updateReservationStatus']);

    Route::get('/reservations/business', [ReservationController::class, 'getBusinessReservations']);
    Route::get("/isReserved/{box_id}", [ReservationController::class, 'checkIsReserved']);
    Route::post("/confirmPickup/{reservation_id}", [ReservationController::class, 'confirmPickup']);
});



Route::middleware("auth:api")->group(function () {
    Route::post("/review", [ReviewController::class, "submitReview"]);
    Route::get("/review/{box_id}", [ReviewController::class, "getBoxReviews"]);
});

Route::get('/user/check-location', [UserController::class, 'checkLocationSet'])->middleware('auth:api');

Route::get("/user/reports", [ReportController::class, "getUserReports"])->middleware('auth:api');



Route::get('/stats/key', [StatisticsController::class, 'getKeyStats']);
Route::get('/stats/detailed', [StatisticsController::class, 'getDetailedStats'])->middleware('auth:api')->middleware('admin');

Route::get('/business/statistics', [BusinessController::class, 'getBusinessStatistics'])
    ->middleware('auth:api');

Route::middleware('auth:api')->group(function () {
    Route::delete('/boxes/business/{id}', [BoxController::class, 'deleteBoxFromBusiness']);
    Route::post('/boxes/business/{id}', [BoxController::class, 'editBox'])->middleware("business");
    Route::patch('/boxes/business/{id}/status', [BoxController::class, 'changeBoxStatus']);

    Route::get('/boxes/search-query', [BoxController::class, 'searchBoxes']);
    
});
