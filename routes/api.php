<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Suadmin\Facturacion\FacturacionController;
use App\Http\Controllers\Suadmin\gyms\GymsController;
use App\Http\Controllers\Suadmin\RolesController;
use App\Http\Controllers\Suadmin\users\UsersController;
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


Route::prefix('gestion')->middleware('jwt.auth')->group(function () {
    Route::get('/roles', [RolesController::class, 'index']);
    Route::get('/users', [UsersController::class, 'users']);
    Route::get('/facturacion', [FacturacionController::class, 'index']);
    
     Route::get('/gyms',        [GymsController::class,       'index']);

Route::post('/gyms/{gym}/branches', [GymsController::class, 'storeBranch']);
Route::put('/gyms/{gym}/branches/{branch}', [GymsController::class, 'updateBranch']);
});