<?php

namespace App\Http\Controllers\Auth;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\UserRegistrationInvitation;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMessages;
use App\Models\User;
use App\spi\DataOrError;
use App\spi\SuperAdminService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
