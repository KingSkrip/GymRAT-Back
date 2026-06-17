<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Suadmin\Clientes\ClientesController;
use App\Http\Controllers\Suadmin\Facturacion\FacturacionController;
use App\Http\Controllers\Suadmin\gyms\GymsController;
use App\Http\Controllers\Suadmin\RolesController;
use App\Http\Controllers\Suadmin\Sucursales\SucursalesController;
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



    Route::get('/clients-list', [GymsController::class, 'clientsList']);
    Route::get('/gyms', [GymsController::class, 'index']);
    Route::get('/gyms/{id}', [GymsController::class, 'show']);
    Route::post('/gyms', [GymsController::class, 'store']);
    Route::put('/gyms/{id}', [GymsController::class, 'update']);
    Route::patch('/gyms/{id}/toggle', [GymsController::class, 'toggle']);
    Route::delete('/gyms/{id}', [GymsController::class, 'destroy']);











    // Sucursales
    Route::get('/sucursales', [SucursalesController::class, 'index']);
    Route::get('/sucursales/{id}', [SucursalesController::class, 'show']);
    Route::put('/sucursales/{id}', [SucursalesController::class, 'update']);
    Route::patch('/sucursales/{id}/toggle', [SucursalesController::class, 'toggle']);
    Route::delete('/sucursales/{id}', [SucursalesController::class, 'destroy']);
    Route::post('/sucursales/{id}/subscriptions', [SucursalesController::class, 'storeSubscription']);
    Route::put('/sucursales/{id}/subscriptions/{subId}', [SucursalesController::class, 'updateSubscription']);
    Route::post('/sucursales/{id}/subscriptions/{subId}/payments', [SucursalesController::class, 'storePayment']);
    Route::get('/gyms-list', [SucursalesController::class, 'gymsList']);
    Route::post('/sucursales', [SucursalesController::class, 'store']);
});


Route::prefix('clientes')->middleware('jwt.auth')->group(function () {
    Route::get('/',           [ClientesController::class, 'index']);
    Route::get('/{id}',       [ClientesController::class, 'show']);
    Route::post('/',          [ClientesController::class, 'store']);
    Route::put('/{id}',       [ClientesController::class, 'update']);
    Route::patch('/{id}/toggle', [ClientesController::class, 'toggle']);
    Route::delete('/{id}',    [ClientesController::class, 'destroy']);
});