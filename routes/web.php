<?php

use App\Http\Controllers\Auth\HomeSuperAdminController;
use App\Http\Controllers\Auth\LoginSuperAdminController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegisterSuperAdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/', [RegisterController::class, 'setupApp'])->name('welcome');
Route::get('/super-admins-auth', [LoginSuperAdminController::class, 'showLoginSuperAdminForm'])->name('auth.super-admins.login');
Route::post('/super-admins-auth', [LoginSuperAdminController::class, 'postLoginSuperAdminForm'])->name('auth.super-admins.login.post');
Route::post('/super-admins-auth/logout', [HomeSuperAdminController::class, 'logout'])->name('super-admin.logout');
Route::get('/super-admins-list', [HomeSuperAdminController::class, 'superAdminList'])->name('auth.super-admins.list');
Route::get('/super-admins-register', [HomeSuperAdminController::class, 'showRegisterSuperAdminForm'])->name('auth.super-admins.register');
Route::post('/super-admins-register', [RegisterSuperAdminController::class, 'registerSuperAdmin'])->name('auth.super-admins.register.post');
Route::post('/super-admins/{userid}/remove-add-to-admin-role', [HomeSuperAdminController::class, 'removeOrAddToAdminRole'])->name('super.admin.add.or.remove.role');
Route::post('/super-admins/{user_id}/activate', [HomeSuperAdminController::class, 'activateUser'])->name('super.admin.activate');
Route::post('/super-admins/{user_id}/deactivate', [HomeSuperAdminController::class, 'deActivateUser'])->name('super.admin.deactivate');
Route::post('/super-admin/password-reset-first-time', [LoginSuperAdminController::class, 'postResetPasswordFirstConnection'])->name('password.reset.first.time.post');
Route::get('/super-admin/tenants', [HomeSuperAdminController::class, 'tenantsList'])->name('super-admin.tenants');
Route::post('/super-admin/tenants/{tenantid}/activate', [HomeSuperAdminController::class, 'activateTenant'])->name('super-admin.tenant.activate');
Route::post('/super-admin/tenants/{tenantid}/deactivate', [HomeSuperAdminController::class, 'deactivateTenant'])->name('super-admin.tenant.deactivate');
Route::get('/super-admin/tenants/{tenantid}/details', [HomeSuperAdminController::class, 'tenantDetail'])->name('tenant.details');




//
//super.admin.deactivate
Auth::routes();


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/home-seller', [App\Http\Controllers\HomeSellerController::class, 'index'])->name('home-seller');

Route::get('/home-super-admin', [HomeSuperAdminController::class, 'superAdminDashboard'])->name('super-admin-dashboard');
/*Route::get('super-admin-required', function (){
    return view('auth.super-admin-required');
});*/
//Route::post('seller-login', [LoginController::class, 'sellerLogin'])->name('seller-login');
