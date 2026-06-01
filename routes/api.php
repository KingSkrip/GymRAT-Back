<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Users\QrController;
use Illuminate\Support\Facades\Route;

Route::options('/{any}', function () {
    return response()->json(['status' => 'ok'], 200);
})->where('any', '.*');


Route::prefix('auth')->group(function () {
    Route::post('sign-in', [AuthController::class, 'signIn']);
    Route::post('sign-up', [AuthController::class, 'signUp']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('sign-in-with-token', [AuthController::class, 'signInWithToken']);
});


Route::prefix('auth')->middleware('jwt.auth')->group(function () {
    Route::post('sign-out', [AuthController::class, 'signOut']);
});


Route::prefix('dash')->middleware('jwt.auth')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
});


Route::prefix('qr')->middleware('jwt.auth')->group(function () {
    Route::post('validate', [QrController::class, 'validateQr']);
});