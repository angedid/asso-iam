<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\Models\User;
use App\spi\TenantService;
use App\spi\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Bridge\RefreshTokenRepository;

class UserController extends Controller
{
    public UserService $userService;
    public function __construct()
    {
        //$this->middleware('auth:api');
        $domainService= new DomainServices();
        $this->userService = new UserService($domainService);
    }
    public function logout(Request $request, $username){
        $resultReturned =  $this->userService->logout($request, $username);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }

    public function isUserExists(Request $request, string $userid)
    {
        $resultReturned =  $this->userService->isUserExists($request, $userid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }

    public function changePassword(Request $request, string $tenant_id, string $user_id)
    {
        $resultReturned =  $this->userService->changePassword($request, $tenant_id, $user_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function activateUser(Request $request, string $tenant_id, string $user_id)
    {
        $resultReturned =  $this->userService->changeUserState($request, $tenant_id, $user_id, true);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function deactivateUser(Request $request, string $tenant_id, string $user_id)
    {
        $resultReturned =  $this->userService->changeUserState($request, $tenant_id, $user_id, false);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function updateUser(Request $request)
    {
        $resultReturned =  $this->userService->updateUser($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function setPhoto(Request $request, string $user_id)
    {
        $resultReturned = $this->userService->setPhoto($request, $user_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }

    public function getPhoto(Request $request, string $user_id)
    {
        $resultReturned = $this->userService->getPhoto($request, $user_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        $path = $resultReturned->data['content'];
        $contents = Storage::disk('public')->get($path);
        return response()->make($contents, 200, array(
            'Content-Type' => (new \finfo(FILEINFO_MIME))->buffer($contents)
        ));
    }


}
