<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\api\DocumentsCreate;
use App\Http\Controllers\api\roleController;
use App\Http\Controllers\api\rolePermisionController;
use App\Http\Controllers\api\TimeableController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkProgramController;
use App\Models\roles;
use App\Models\timetable;

/*
|--------------------------------------------------------------------------
| API Routes - SIPMA HMJ
|--------------------------------------------------------------------------
|
| Prefix: /api
| Auth: Laravel Sanctum (Bearer Token)
|
*/

// ── Public: Auth ──────────────────────────────────────────────────────────
Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login']);

// ── Public: Events (Read Only & Conflict Check) ───────────────────────────
Route::get('events',                  [EventController::class, 'index']);
Route::get('events/{id}',             [EventController::class, 'show']);
Route::get('/events/public',          [EventController::class, 'index']);
Route::post('events/check-conflict',  [EventController::class, 'checkConflict']);

//get timetable
Route::get('add_timetable_index', [TimeableController::class, 'index']);


// ── Public: Divisions (Read Only) ─────────────────────────────────────────
Route::get('divisions',       [DivisionController::class, 'index']);
Route::get('divisions/{id}',  [DivisionController::class, 'show']);

// ── Public: Work Programs (Read Only) ─────────────────────────────────────
Route::get('work-programs',                     [WorkProgramController::class, 'index']);
Route::get('work-programs/public',              [WorkProgramController::class, 'index']);
Route::get('work-programs/{id}',                [WorkProgramController::class, 'show']);
Route::get('divisions/{id}/work-programs',      [WorkProgramController::class, 'getByDivision']);

// ── Protected Routes (Bearer Token Required) ──────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('logout',   [AuthController::class, 'logout']);
    Route::get('me',        [AuthController::class, 'me']);
    Route::get('user',      [AuthController::class, 'me']);
    Route::post('refresh',  [AuthController::class, 'refresh']);


    Route::get('my_division',       [DivisionController::class, 'get_my_division']);


    // Dashboard
    Route::get('dashboard/stats', [UserController::class, 'dashboardStats']);

    // Events — create/edit/delete (role-checked inside controller)
    Route::post('events',                    [EventController::class, 'store']);
    Route::put('events/{id}',                [EventController::class, 'update']);
    Route::delete('events/{id}',             [EventController::class, 'destroy']);
    Route::patch('events/{id}/status',       [EventController::class, 'updateStatus']);

    // Divisions & Work Programs & Members — update info & sub-resources
    Route::put('divisions/{id}',                         [DivisionController::class, 'update']);
    Route::post('divisions/{id}/work-programs',          [DivisionController::class, 'addWorkProgram']);
    Route::post('divisions/{id}/galleries',              [DivisionController::class, 'addGallery']);
    Route::post('divisions/{id}/members',                [DivisionController::class, 'addMember']);
    Route::put('division-members/{id}',                  [DivisionController::class, 'updateMember']);
    Route::delete('division-members/{id}',               [DivisionController::class, 'deleteMember']);
    Route::put('work-programs/{id}',                     [WorkProgramController::class, 'update']);
    Route::delete('work-programs/{id}',                  [WorkProgramController::class, 'destroy']);

    // Users — management (pimpinan only, checked inside)
    Route::get('users',          [UserController::class, 'index']);
    Route::post('users',          [UserController::class, 'store']);
    Route::get('users/{id}',     [UserController::class, 'show']);
    Route::put('users/{id}',     [UserController::class, 'update']);
    Route::post('users/{id}',  [UserController::class, 'destroy']);


    //timeable

    Route::post('add_timetable', [TimeableController::class, 'store']);
    Route::post('update_timetable/{id}', [TimeableController::class, 'update']);
    Route::post('delete_timetable/{id}', [TimeableController::class, 'delete']);

    //document
    Route::post('add_documents_to_timeables/{id}', [DocumentsCreate::class, 'store']);
    Route::post('update_documents_to_timeables/{id}', [DocumentsCreate::class, 'update']);
    Route::post('delete_documents_to_timeables/{id}', [DocumentsCreate::class, 'destroy']);

    // roless
    Route::get('roles',          [roleController::class, 'index']);
    Route::post('roles',          [roleController::class, 'store']);
    Route::put('roles/{id}',          [roleController::class, 'update']);
    Route::delete('roles/{id}',          [roleController::class, 'destroy']);


    // roles permision
    Route::get('role-permissions',          [rolePermisionController::class, 'index']);
    Route::post('role-permissions',          [rolePermisionController::class, 'store']);
    Route::put('role-permissions/{id}',          [rolePermisionController::class, 'update']);
    Route::delete('role-permissions/{id}',          [rolePermisionController::class, 'destroy']);
});
