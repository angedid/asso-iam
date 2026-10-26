<?php

namespace App\Http\Controllers\Auth;

use App\Domain\DomainServices\DomainServices;
use App\Http\Controllers\Controller;
use App\spi\SuperAdminService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginSuperAdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    public SuperAdminService $superAdminService;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = '/home-super-admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
        $this->superAdminService = new SuperAdminService(new DomainServices());
    }
    public function username()
    {
        return 'username';
    }

    public function showLoginSuperAdminForm()
    {
        return view('auth.login-super-admin');
    }

    public function postLoginSuperAdminForm(Request $request)
    {
        return $this->superAdminService->postLoginSuperAdmin($request);
    }

    public function postResetPasswordFirstConnection(Request $request)  {
        return $this->superAdminService->postResetPasswordFirstConnection($request);
    }
}
