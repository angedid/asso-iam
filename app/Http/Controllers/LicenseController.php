<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\spi\AssoLicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public AssoLicenseService $assoLicenseService;
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->assoLicenseService = new AssoLicenseService(new DomainServices());
    }

    public function getLicenceDetails(Request $request, string $tenant_id)
    {
        $resultReturned =  $this->assoLicenseService->getLicenceDetails($request, $tenant_id);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }
}
