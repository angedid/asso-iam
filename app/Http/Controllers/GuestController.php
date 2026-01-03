<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\spi\TenantService;
use Illuminate\Http\Request;

class GuestController extends Controller
{

    public TenantService $tenantService;
    public function __construct()
    {
        $this->tenantService = new TenantService(new DomainServices());
    }
    public function getTenants(Request $request)
    {
        $resultReturned =  $this->tenantService->getTenants($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }
}
