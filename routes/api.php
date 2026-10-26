<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ScopeController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/guest', function (Request $request) {
    return $request->url();
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

//Route::post('/oauth/token', [RegisterController::class, 'getAccessToken'])->name('asso-oauth.token');

//
//?full_name=NKALLA EHAWE&email=lofombocm@gmail.com&tenant_id=a2d09d92-b3a8-46ae-8f76-baf92b0f538f&invited_by=9a48cbcb-a3f1-4662-a827-cb3d168d51b9
//
Route::post('/tenants', [RegisterController::class, 'provisionTenant']);
Route::post('/users-registrations-invitations', [RegisterController::class, 'inviteUser'])->middleware('auth:api');
Route::post('/users', [RegisterController::class, 'registerUser'])->middleware('auth:api');;
Route::post('/sellers', [RegisterController::class, 'registerSeller']);
Route::post('/logout/{username}', [UserController::class, 'logout'])->middleware('auth:api');
Route::get('/users/{userid}/exist', [UserController::class, 'isUserExists'])->middleware('auth:api');
Route::post('/roles', [RoleController::class, 'createNewRole'])->middleware('auth:api');
Route::get('/roles', [RoleController::class, 'retrieveAllRoles'])->middleware('auth:api');
Route::post('/roles/{roleid}/groups', [RoleController::class, 'addGroupToRole'])->middleware('auth:api');
Route::delete('/roles/{roleid}/groups', [RoleController::class, 'removeGroupInRole'])->middleware('auth:api');
Route::get('/roles/{roleid}/groups', [RoleController::class, 'retrieveGroupsPlayingRole'])->middleware('auth:api');
Route::get('/roles/{roleid}/not-groups', [RoleController::class, 'retrieveGroupsNotPlayingRole'])->middleware('auth:api');
Route::post('/roles/{roleid}/users', [RoleController::class, 'addUserToRole'])->middleware('auth:api');
Route::delete('/roles/{roleid}/users', [RoleController::class, 'removeUserInRole'])->middleware('auth:api');
Route::get('/roles/{roleid}/not-users',[RoleController::class, 'retrieveUsersNotPlayingRole'])->middleware('auth:api');
Route::get('/roles/{roleid}/users/{userid}/is-in-role', [RoleController::class, 'isUserInRole'])->middleware('auth:api');
Route::post('/groups', [GroupController::class, 'createNewGroup'])->middleware('auth:api');
Route::get('/groups', [GroupController::class, 'retrieveAllGroup'])->middleware('auth:api');
Route::post('/groups/{groupid}/members', [GroupController::class, 'addGroupMemberToGroup'])->middleware('auth:api');
Route::delete('/groups/{groupid}/members', [GroupController::class, 'removeGroupMemberToGroup'])->middleware('auth:api');
Route::get('/groups/{groupid}/members', [GroupController::class, 'retrieveGroupMembers'])->middleware('auth:api');
Route::get('/groups/{groupid}/not-members', [GroupController::class, 'notGroupMembers'])->middleware('auth:api');
Route::get('/scopes', [ScopeController::class, 'retrieveAllScopes'])->middleware('auth:api');
Route::post('/roles/{roleid}/scopes', [RoleController::class, 'addScopesToRole'])->middleware('auth:api');
Route::get('/roles/{roleid}/scopes', [RoleController::class, 'retrieveAllScopeByRoleId'])->middleware('auth:api');
Route::delete('/roles/{roleid}/scopes', [RoleController::class, 'removeScopeInRole'])->middleware('auth:api');

Route::put('/tenants/{tenant_id}/activate', [TenantController::class, 'activateTenant'])->middleware('auth:api');
Route::put('/tenants/{tenant_id}/deactivate', [TenantController::class, 'deactivateTenant'])->middleware('auth:api');
Route::post('/password-reset-request', [RegisterController::class, 'requestPasswordReset']);
Route::post('/password-reset-request/{passwordresetinvitationid}', [RegisterController::class, 'resetPassword']);
Route::post('/tenants/{tenant_id}/users/{user_id}/change-password', [UserController::class, 'changePassword'])->middleware('auth:api');
Route::post('/tenants/{id}/logo', [TenantController::class, 'setLogo'])->middleware(['auth:api']);
Route::get('/tenants/{id}/logo', [TenantController::class, 'getLogo']);

Route::put('/tenants/{tenant_id}/users/{user_id}/activate', [UserController::class, 'activateUser'])->middleware('auth:api');
Route::put('/tenants/{tenant_id}/users/{user_id}/deactivate', [UserController::class, 'deactivateUser'])->middleware('auth:api');
Route::put('/users', [UserController::class, 'updateUser'])->middleware('auth:api');
Route::post('/users/{id}/photo', [UserController::class, 'setPhoto'])->middleware(['auth:api']);
Route::get('/users/{id}/photo', [UserController::class, 'getPhoto']);
Route::put('/tenants', [TenantController::class, 'updateTenant'])->middleware(['auth:api']);;
Route::get('/tenants', [GuestController::class, 'getTenants']);
Route::get('/tenants/{tenant_id}', [GuestController::class, 'retrieveTenantById'])/*->middleware('auth:api')*/;
Route::post('/payments', [ApiController::class, 'makeServicePayment'])->middleware('rabbitmq.client');
Route::get('/tenants/{tenant_id}/valid-licences', [LicenseController::class, 'getLicenceDetails'])->middleware('auth:api');


Route::post('/orders', [ApiController::class, 'placeOrder'])->middleware('rabbitmq.client');
Route::put('/orders/{order_id}/paid-order', [ApiController::class, 'updateOrderOrderPaid'])->middleware('rabbitmq.client');
Route::put('/orders/{order_id}/cancelled-order', [ApiController::class, 'updateOrderOrderCancelled'])->middleware('rabbitmq.client');








