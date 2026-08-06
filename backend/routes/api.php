<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminSalesTargetsController;
use App\Http\Controllers\Admin\AdminRolesController;
use App\Http\Controllers\Admin\AdminLocationsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSalesManagerController;
use App\Http\Controllers\Admin\AdminAreaManagerController;
use App\Http\Controllers\Admin\AdminSupervisorController;
use App\Http\Controllers\Admin\AdminSalesRepController;
use App\Http\Controllers\Admin\AdminPanelController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'],function (){

    Route::post('login',           [AuthController::class, 'login']);
    Route::post('logout',          [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh',         [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me',               [AuthController::class, 'me'])->middleware('is_active_user');
    Route::post('update-password', [AuthController::class, 'updatePassword'])->middleware('auth:api');
});

// ─── Admin API ───────────────────────────────────────────────────────────────
Route::prefix('admin')->group(function () {

    // Auth (public)
    Route::post('auth/login',   [AdminAuthController::class, 'login']);
    Route::post('auth/refresh', [AdminAuthController::class, 'refresh']);

    // Protected admin routes
    Route::middleware('admin_auth')->group(function () {

        // Auth
        Route::get('auth/me',               [AdminAuthController::class, 'me']);
        Route::post('auth/logout',          [AdminAuthController::class, 'logout']);
        Route::post('auth/update-password', [AdminAuthController::class, 'updatePassword']);

        // Dashboard
        Route::get('dashboard',          [AdminDashboardController::class, 'index']);
        Route::get('dashboard/overview', [AdminDashboardController::class, 'overview']);
        Route::get('dashboard/working-days',    [AdminDashboardController::class, 'getWorkingDays']);
        Route::put('dashboard/working-days',    [AdminDashboardController::class, 'updateWorkingDays']);
        Route::delete('dashboard/working-days', [AdminDashboardController::class, 'clearWorkingDays']);

        // Role-based dashboards
        Route::get('sales-managers/list',  [AdminSalesManagerController::class, 'list']);
        Route::get('sales-managers/{id}',  [AdminSalesManagerController::class, 'show']);

        Route::get('area-managers/list',   [AdminAreaManagerController::class, 'list']);
        Route::get('area-managers/{id}',   [AdminAreaManagerController::class, 'show']);

        Route::get('supervisors/list',     [AdminSupervisorController::class, 'list']);
        Route::get('supervisors/{id}',     [AdminSupervisorController::class, 'show']);

        Route::get('sales-reps/list',      [AdminSalesRepController::class, 'list']);
        Route::get('sales-reps/{id}',      [AdminSalesRepController::class, 'show']);

        // Admin Panel
        Route::get('panel/targets',                [AdminPanelController::class, 'targets']);
        Route::put('panel/targets/{userId}',       [AdminPanelController::class, 'updateTarget']);
        Route::get('panel/commission-schemes',     [AdminPanelController::class, 'commissionSchemes']);
        Route::get('panel/sales-reps',             [AdminPanelController::class, 'salesReps']);
        Route::get('panel/supervisors',            [AdminPanelController::class, 'supervisors']);

        // Users
        Route::get('users',                   [AdminUsersController::class, 'index']);
        Route::post('users',                  [AdminUsersController::class, 'store']);
        Route::get('users/hierarchy',         [AdminUsersController::class, 'hierarchy']);
        Route::get('users/roles',             [AdminUsersController::class, 'roles']);
        Route::get('users/sync-erp',          [AdminUsersController::class, 'syncFromERP']);
        Route::get('users/{user}',            [AdminUsersController::class, 'show']);
        Route::put('users/{user}',            [AdminUsersController::class, 'update']);
        Route::delete('users/{user}',         [AdminUsersController::class, 'destroy']);
        Route::get('users/{user}/children',   [AdminUsersController::class, 'children']);
        Route::post('users/{user}/children',  [AdminUsersController::class, 'addChildren']);
        Route::delete('users/{user}/remove-child', [AdminUsersController::class, 'removeChild']);
        Route::get('users/{user}/sales',      [AdminUsersController::class, 'sales']);

        // Sales Targets
        Route::get('sales-targets',                          [AdminSalesTargetsController::class, 'index']);
        Route::post('sales-targets',                         [AdminSalesTargetsController::class, 'store']);
        Route::get('sales-targets/available-users',         [AdminSalesTargetsController::class, 'availableUsers']);
        Route::get('sales-targets/{id}',                    [AdminSalesTargetsController::class, 'show']);
        Route::delete('sales-targets/{id}',                 [AdminSalesTargetsController::class, 'destroy']);
        Route::post('sales-targets/{id}/assign-users',      [AdminSalesTargetsController::class, 'assignUsers']);
        Route::delete('sales-targets/user-target/{userTargetId}', [AdminSalesTargetsController::class, 'removeUser']);
        Route::post('sales-targets/{id}/rules',             [AdminSalesTargetsController::class, 'addRule']);
        Route::delete('sales-targets/rules/{ruleId}',       [AdminSalesTargetsController::class, 'removeRule']);

        // Roles
        Route::get('roles',                        [AdminRolesController::class, 'index']);
        Route::get('roles/{role}',                 [AdminRolesController::class, 'show']);
        Route::put('roles/{role}/commission',      [AdminRolesController::class, 'updateCommission']);

        // Locations
        Route::get('locations',              [AdminLocationsController::class, 'index']);
        Route::post('locations',             [AdminLocationsController::class, 'store']);
        Route::get('locations/{location}',   [AdminLocationsController::class, 'show']);
        Route::put('locations/{location}',   [AdminLocationsController::class, 'update']);
        Route::delete('locations/{location}',[AdminLocationsController::class, 'destroy']);
    });
});

// ─── Mobile App API ───────────────────────────────────────────────────────────
Route::group(['middleware' => ['auth:api','is_active_user']], function () {
    Route::get('my-sales/{oid?}',[\App\Http\Controllers\SalesController::class,'mySales']);
    Route::get('my-target',[\App\Http\Controllers\SalesController::class,'myTarget']);
    Route::get('commission-percentage',[\App\Http\Controllers\SalesController::class,'commissionPercentage']);
    Route::get('current-sales/{id?}',[\App\Http\Controllers\SalesController::class,'currentSales']);
    Route::get('my-children/{id?}',[\App\Http\Controllers\UserApiController::class,'myChildren']);
    Route::get('my-team',[\App\Http\Controllers\UserApiController::class,'myTeam']);
    Route::get('team-sales/{oid?}',[\App\Http\Controllers\SalesController::class,'teamSales']);
    Route::get('year-sales/{oid?}',[\App\Http\Controllers\SalesController::class,'yearSales']);
    Route::get('week-sales/{oid?}',[\App\Http\Controllers\SalesController::class,'weekSales']);
    Route::get('year-total-sales/{id?}',[\App\Http\Controllers\SalesController::class,'yearTotalSales']);
});
