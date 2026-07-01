<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Coach\CoachAssessmentController;
use App\Http\Controllers\Coach\CoachDietController;
use App\Http\Controllers\Coach\CoachPhotoController;
use App\Http\Controllers\Coach\CoachProgressController;
use App\Http\Controllers\Coach\CoachWorkoutController;
use App\Http\Controllers\Exercises\ExerciseController;
use App\Http\Controllers\Membresias\MembershipController;
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
    Route::post('/roles', [RolesController::class, 'storeRole']);
    Route::get('/roles/{id}', [RolesController::class, 'showRole']);
    Route::put('/roles/{id}', [RolesController::class, 'updateRole']);
    Route::delete('/roles/{id}', [RolesController::class, 'destroyRole']);

    Route::get('/sub-roles', [RolesController::class, 'indexSubRoles']);
    Route::post('/sub-roles', [RolesController::class, 'storeSubRole']);
    Route::get('/sub-roles/{id}', [RolesController::class, 'showSubRole']);
    Route::put('/sub-roles/{id}', [RolesController::class, 'updateSubRole']);
    Route::delete('/sub-roles/{id}', [RolesController::class, 'destroySubRole']);

    Route::get('/users', [UsersController::class, 'users']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::put('/users/{id}', [UsersController::class, 'update']);
    Route::delete('/users/{id}', [UsersController::class, 'destroy']);
    Route::get('/users/{id}', [UsersController::class, 'show']);


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

Route::prefix('users')->middleware('jwt.auth')->group(function () {
    Route::post('/{id}/membership', [MembershipController::class, 'store']);
});


Route::prefix('coach')->middleware('jwt.auth')->group(function () {

    // ── Dietas ─────────────────────────────────────────────────────────
    Route::prefix('dietas')->group(function () {
        Route::get('/',                        [CoachDietController::class, 'index']);
        Route::post('/',                       [CoachDietController::class, 'store']);
        Route::get('/{id}',                    [CoachDietController::class, 'show']);
        Route::put('/{diet}',                  [CoachDietController::class, 'update']);
        Route::delete('/{diet}',               [CoachDietController::class, 'destroy']);
        Route::patch('/{diet}/toggle',         [CoachDietController::class, 'toggleActive']);
        Route::post('/{diet}/duplicate',       [CoachDietController::class, 'duplicate']);
        Route::get('/cliente/{userId}/activa', [CoachDietController::class, 'active']);
    });

    // ── Rutinas ────────────────────────────────────────────────────────
    Route::prefix('rutinas')->group(function () {
        Route::get('/',                  [CoachWorkoutController::class, 'index']);
        Route::post('/',                 [CoachWorkoutController::class, 'store']);
        Route::get('/{id}',              [CoachWorkoutController::class, 'show']);
        Route::put('/{workout}',         [CoachWorkoutController::class, 'update']);
        Route::delete('/{workout}',      [CoachWorkoutController::class, 'destroy']);
        Route::patch('/{workout}/toggle', [CoachWorkoutController::class, 'toggleActive']);
        Route::post('/{workout}/duplicate', [CoachWorkoutController::class, 'duplicate']);
    });

    // ── Progreso / Evaluaciones ────────────────────────────────────────
    Route::prefix('clientes/{userId}')->where(['userId' => '[0-9]+'])->group(function () {
        Route::get('/evaluaciones',        [CoachAssessmentController::class, 'history']);
        Route::get('/evaluaciones/ultima', [CoachAssessmentController::class, 'latest']);
        Route::get('/progreso/resumen',    [CoachAssessmentController::class, 'summary']);
        Route::get('/progreso/graficas',   [CoachAssessmentController::class, 'charts']);
        Route::post('/evaluaciones',            [CoachAssessmentController::class, 'store']);
        Route::put('/evaluaciones/{assessmentId}',    [CoachAssessmentController::class, 'update']);
        Route::delete('/evaluaciones/{assessmentId}', [CoachAssessmentController::class, 'destroy']);
        Route::get('/evaluaciones/{assessmentId}',    [CoachAssessmentController::class, 'show']);
        Route::get('/progreso/historial',  [CoachProgressController::class, 'history']);
        Route::get('/progreso/summary',    [CoachProgressController::class, 'summary']);
        Route::get('/progreso/charts',     [CoachProgressController::class, 'charts']);
        Route::get('/progreso/latest',     [CoachProgressController::class, 'latest']);
    });

    // ── Fotos de progreso ──────────────────────────────────────────────
    Route::prefix('evaluaciones/{assessmentId}/fotos')->group(function () {
        Route::get('/',             [CoachPhotoController::class, 'show']);
        Route::post('/',            [CoachPhotoController::class, 'store']);
        Route::delete('/{type}',    [CoachPhotoController::class, 'destroy']);
    });
});


Route::prefix('exercises')->middleware('jwt.auth')->group(function () {
    Route::get('/', [ExerciseController::class, 'index']);
    Route::post('/', [ExerciseController::class, 'store']);
    Route::get('/{exercise}', [ExerciseController::class, 'show']);
    Route::put('/{exercise}', [ExerciseController::class, 'update']);
    Route::patch('/{exercise}', [ExerciseController::class, 'update']);
    Route::delete('/{exercise}', [ExerciseController::class, 'destroy']);

    Route::patch('/{exercise}/toggle-active', [ExerciseController::class, 'toggleActive']);
});