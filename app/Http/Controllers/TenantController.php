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

class TenantController extends Controller
{
    public TenantService $tenantService;
    public function __construct()
    {
        $this->middleware('auth:api');
        $domainService= new DomainServices();
        $this->tenantService = new TenantService($domainService);
    }

    public function deactivateTenant(Request $request, string $tenant_id)
    {
        $resultReturned = $this->tenantService->deactivateTenant($request, $tenant_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function activateTenant(Request $request, string $tenant_id)
    {
        $resultReturned = $this->tenantService->activateTenant($request, $tenant_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function setLogo(Request $request, string $tenant_id)
    {
        $resultReturned = $this->tenantService->setLogo($request, $tenant_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function getLogo(Request $request, string $tenant_id)
    {
        $resultReturned = $this->tenantService->getLogo($request, $tenant_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }

        $path = $resultReturned->data['content'];
        $contents = Storage::disk('public')->get($path);
        return response()->make($contents, 200, array(
            'Content-Type' => (new \finfo(FILEINFO_MIME))->buffer($contents)
        ));
        //return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function updateTenant(Request $request)
    {
        $resultReturned =  $this->tenantService->updateTenant($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }
}
