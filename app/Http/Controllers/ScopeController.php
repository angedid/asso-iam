<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\spi\ScopeService;
use Illuminate\Http\Request;

class ScopeController extends Controller
{
    public ScopeService $scopeService;
    public function __construct()
    {
        $this->middleware('auth:api');
        $domainService= new DomainServices();
        $this->scopeService = new ScopeService($domainService);
    }
    /*public function createNewGroup(Request $request)
    {
        $resultReturned = $this->groupService->createNewGroup($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }*/

    public function retrieveAllScopes(Request $request)
    {
        $resultReturned = $this->scopeService->retrieveAllScopes($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }
}
