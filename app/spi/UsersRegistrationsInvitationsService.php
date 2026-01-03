<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\Tenant;
use App\Domain\Model\UserRegistrationInvitation;
use App\Jobs\ProcessSendEMailUserInvitationJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UsersRegistrationsInvitationsService
{
    protected DomainServices $domainServices;

    public function __construct(DomainServices $domainServices)
    {
        $this->domainServices = $domainServices;
    }

    public function inviteUser(Request $request): DataOrError
    {
        $validatorInvitation = DomainServices::makeUserRegistrationInvitationValidator($request);

        if ($validatorInvitation->fails()) {
            return new DataOrError(null, $validatorInvitation->errors()->all(),true);
        }

        $tenant = Tenant::where('id', trim($request->get('tenant_id')))->where('active', true)->first();
        if ($tenant == null) {
            return new DataOrError(null, ['Tenant not found'],true);
        }

        $user = User::where('id', trim($request->get('invited_by')))->first();
        if ($user == null) {
            return new DataOrError(null, ['user not found'],true);
        }

        if ($user->tenant_id != $tenant->id) {
            return new DataOrError(null, ['User not in tenant'],true);
        }

        $invitationid = Str::uuid()->toString();
        $invitedAt = Carbon::now();
        $expires_at = Carbon::now()->addDays(30);
        $url = env('ASSO_CLIENT_URL').'/users-registrations-invitations?id='.$invitationid;
        $tenant = Tenant::where('id', trim($request->get('tenant_id')))->first();

        $invitationData = [
            'id' => $invitationid,
            'tenant_id' => trim($request->get('tenant_id')),
            'full_name' => trim($request->get('full_name')),
            'email' => trim($request->get('email')),
            'phone_number' => trim($request->get('phone_number')),
            'invited_by' => trim($request->get('invited_by')),
            'invited_at' => $invitedAt,
            'expires_at' => $expires_at,
            'active' => true,
            'state' => 'PENDING',
            'used' => false,
            'url' => $url,
        ];

        $userRegistrationInvitation = UserRegistrationInvitation::create($invitationData);
        $emaildata = [
            'email' => trim($request->get('email')),
            'name' => trim($request->get('full_name')),
            'url' => $url,
            'tenant_name' => $tenant->name,
            ];

        ProcessSendEMailUserInvitationJob::dispatch($emaildata);

        $dataOrError = new DataOrError(['invitation' => $userRegistrationInvitation], null,false);
        return $dataOrError;
    }
}
