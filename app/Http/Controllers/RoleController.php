<?php

namespace App\Http\Controllers;

use App\Domain\DomainServices\DomainServices;
use App\spi\RoleService;
use App\spi\UserService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public RoleService $roleService;
    public function __construct()
    {
        $this->middleware('auth:api');
        $domainService= new DomainServices();
        $this->roleService = new RoleService($domainService);
    }
    public function createNewRole(Request $request)
    {
        $resultReturned = $this->roleService->createNewRole($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    //AccessTokenCreated
    public function retrieveAllRoles(Request $request)
    {
        $resultReturned = $this->roleService->retrieveAllRoles($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function addGroupToRole(Request $request, string $roleid)
    {

        $resultReturned = $this->roleService->addGroupToRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }
    public function removeGroupInRole(Request $request, string $roleid)
    {

        $resultReturned = $this->roleService->removeGroupInRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }

    public function removeUserInRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->removeUserInRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }

    public function removeScopeInRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->removeScopeInRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }




    public function retrieveGroupsPlayingRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->retrieveGroupsPlayingRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);

    }

    public function retrieveGroupsNotPlayingRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->retrieveGroupsNotPlayingRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function addUserToRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->addUserToRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function isUserInRole(Request $request, string $roleid, string $userid)
    {
        $resultReturned = $this->roleService->isUserInRole($request, $roleid, $userid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function retrieveUsersNotPlayingRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->retrieveUsersNotPlayingRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function addScopesToRole(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->addScopesToRole($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function retrieveAllScopeByRoleId(Request $request, string $roleid)
    {
        $resultReturned = $this->roleService->retrieveAllScopeByRoleId($request, $roleid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }




}
