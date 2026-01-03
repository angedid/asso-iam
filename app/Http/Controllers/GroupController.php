<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\spi\GroupService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public GroupService $groupService;
    public function __construct()
    {
        $this->middleware('auth:api');
        $domainService= new DomainServices();
        $this->groupService = new GroupService($domainService);
    }
    public function createNewGroup(Request $request)
    {
        $resultReturned = $this->groupService->createNewGroup($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function retrieveAllGroup(Request $request)
    {
        $resultReturned = $this->groupService->retrieveAllGroup($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function addGroupMemberToGroup(Request $request, string $groupid)
    {
        $resultReturned = $this->groupService->addGroupMemberToGroup($request, $groupid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function retrieveGroupMembers(Request $request, string $groupid)
    {
        $resultReturned = $this->groupService->retrieveGroupMembers($request, $groupid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function notGroupMembers(Request $request, string $groupid)
    {
        $resultReturned = $this->groupService->notGroupMembers($request, $groupid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function removeGroupMemberToGroup(Request $request, string $groupid)
    {
        $resultReturned = $this->groupService->removeGroupMemberToGroup($request, $groupid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

}
