<?php

namespace App\Http\Controllers\Auth;

use App\Domain\DomainServices\DomainServices;
use App\Http\Controllers\Controller;
use App\spi\SuperAdminService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;

class RegisterSuperAdminController extends Controller
{
    use RegistersUsers;
    //protected $redirectTo = '/home-super-admin';
    public SuperAdminService $superAdminService;
    public function __construct()
    {
        $this->middleware('guest');
        $domainService= new DomainServices();
        $this->superAdminService = new SuperAdminService($domainService);
    }

    public function registerSuperAdmin(Request $request){
        $request->merge([
            'password' => 'Asso123!!',
            'password_confirmation' => 'Asso123!!',
        ]);
        return $this->superAdminService->registerSuperAdmin($request);
    }


}
