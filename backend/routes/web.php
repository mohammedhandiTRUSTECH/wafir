<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return response()->json(['Welcome to the API']);
});

Route::group(['middleware' => ['auth']], function () {
//    Route::get('users', [UserController::class, 'index'])->name('users.index');
//    Route::post('users', [UserController::class, 'store'])->name('users.store');
//    Route::get('users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
//    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
//    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
//    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
//    Route::get('update-erp-users', [UserController::class, 'updateFromERP'])->name('users.get-from-erp');
//    Route::resource('sales-targets', \App\Http\Controllers\SaleTargetController::class);
//    Route::get('add-commission-rule',[\App\Http\Controllers\SaleTargetController::class,'addCommissionRule']);
//    Route::get('user-children/{user}',[UserController::class,'userChildren'])->name('users.user-children');
//    Route::post('user-children/{user}',[UserController::class,'addUserChildren'])->name('users.add-user-children');
//    Route::delete('remove-child/{user}',[UserController::class,'removeChild'])->name('users.remove-child');
//    Route::get('sales-target/add-users/{id}',[App\Http\Controllers\SaleTargetController::class, 'addUsers'])->name('sales-targets.add-users');
//    Route::post('sales-target/add-users/{id}',[App\Http\Controllers\SaleTargetController::class, 'storeUsers'])->name('sales-targets.store-users');
//    Route::delete('user-target/{id}',[App\Http\Controllers\SaleTargetController::class, 'destroyUserTarget'])->name('sales-targets.destroy');
//    Route::get('users-hierarchy',[UserController::class,'userHierarchy'])->name('users.hierarchy');
//    Route::get('user-sales/{user}',[UserController::class,'userSales'])->name('users.sales');
//    Route::get('import-sales',[UserController::class,'importSales'])->name('users.import-sales');
//    Route::post('import-sales',[UserController::class,'import'])->name('users.import');
//    Route::get('roles',[\App\Http\Controllers\RoleController::class,'index'])->name('roles.index');
//    Route::get('roles/{role}',[\App\Http\Controllers\RoleController::class,'edit'])->name('roles.edit');
//    Route::put('roles/{role}',[\App\Http\Controllers\RoleController::class,'update'])->name('roles.update');
//    Route::resource('locations', \App\Http\Controllers\LocationController::class);
});
//Auth::routes();

//Route::get('user-oid/{id}',[UserController::class,'getOid']);
//Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
