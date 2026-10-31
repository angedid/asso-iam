<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use App\Domain\Model\Scope;
use Illuminate\Http\Request;

class ScopeService
{

    protected DomainServices $domainServices;

    public function __construct(DomainServices $domainServices)
    {
        $this->domainServices = $domainServices;
    }

    /*public function createNewGroup(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $validatorGroup = DomainServices::makeGroupValidator($request);

        if ($validatorGroup->fails()) {
            return new DataOrError(null, $validatorGroup->errors()->all(),true);
        }

        $group = Group::where('name', trim($request->get('name')))
            ->where('tenant_id', trim($request->get('tenant_id')))->first();
        if ($group !== null){
            return new DataOrError(null, [__("Nom deja utilise pour cette entreprise.")],true);
        }

        $groupid = Str::uuid()->toString();

        $groupData = [
            'id' => $groupid,
            'tenant_id' => trim($request->get('tenant_id')),
            'name' => trim($request->get('name')),
            'description' => trim($request->get('description')),
            'members' => json_encode([]),
            'active' => true,
        ];

        $groupe = Group::create($groupData);

        $dataOrError = new DataOrError(['group' => $groupe], null,false);

        ProcessMessages::dispatch(env('GROUP_CREATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }*/

    public function retrieveAllScopes(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_ROLE_AND_GROUP", $scopes) && !in_array("SCOPE_READ_ROLE_AND_GROUP", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $user = $request->user();
        return new DataOrError(['scopes' => Scope::all()], null,false);
    }
}
