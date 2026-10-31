<?php

namespace App\spi;

use App\Constances;
use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use App\Domain\Model\PasswordResetInvitation;
use App\Domain\Model\Tenant;
use App\Domain\Model\UserRegistrationInvitation;
use App\Jobs\ProcessMessages;
use App\Jobs\ProcessSendEMailPasswordResetInvitationJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{

    protected DomainServices $domainServices;

    public function __construct(DomainServices $domainServices)
    {
        $this->domainServices = $domainServices;
    }

    public function registerUser(Request $request): DataOrError
    {
        $validatorUser = DomainServices::makeUserValidator($request);

        if ($validatorUser->fails()) {
            return new DataOrError(null, $validatorUser->errors()->all(),true);
        }

        $invitation = UserRegistrationInvitation::where('id', trim($request->get('invitation_id')))->first();
        if ($invitation == null) {
            return new DataOrError(null, [__("Invitation introuvable")],true);
        }

        if (!$invitation->isValid()) {
            if (!$invitation->active){
                return new DataOrError(null, [__("Invitation desactivee")],true);
            }
            return new DataOrError(null, [__("Invitation expiree")],true);
        }

        $userid = Str::uuid()->toString();

        $userData = [
            'id' => $userid,
            'tenant_id' => $invitation->tenant_id,
            'type' => 'MERCHANT_USER',
            'full_name' => trim($request->get('full_name')),
            'username' => trim($request->get('username')) . '@' . $invitation->tenant_id,
            'local_username' => trim($request->get('username')),
            'email' => $invitation->email,
            'password' => Hash::make($request->get('password')),
            'phone_number' => trim($request->get('phone_number')),
            'location' => trim($request->get('location')),
            'region' => trim($request->get('region')),
            'active' => true,
            'network' => json_encode([]),
        ];

        $user = User::create($userData);

        if ($request->file('photo') != null) {
            $pathusrphoto = 'users/' . $userid . '/photos';
            $photousrPath = $request->file('photo')->store($pathusrphoto, 'public');
            $user->photo = $photousrPath;
        }

        if($request->filled('country')){
            $country = country(strtolower($request->get('country')));
            $countryCode = $country->getIsoAlpha2();
            $user->country = $countryCode;
        }

        $user->save();

        $user->password = '';

        $dataOrError = new DataOrError(['user' => $user], null,false);

        ProcessMessages::dispatch(env('USER_REGISTERED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function logout(Request $request, $username): DataOrError
    {
        try {

            /*$usernameAndTntId = preg_split("@", $username);
            $response = Http::get(url('api/tenants/' . $usernameAndTntId[1]));
            Log::info(url('api/tenants/' . $usernameAndTntId[1]) . $response->body());*/

            //Log::info(route('logout'));

            //$response = Http::asForm()->post(route('logout'), []);

            if (Auth::guard('api')->check()) {
                $access_token = $request->bearerToken();
                //Log::info("access_token: $access_token");
                $tokendata = explode('.', $access_token);
                $jsondata = json_decode(base64_decode($tokendata[1]));
                $jti = $jsondata->jti;
                //Log::info("jti: $jti");
                $user = User::where('username', $username)->first();

                if ($user === null) {
                    return new DataOrError(null, [__("Utilisateur non reconnu!.")],true);
                }

                //$tokenRepository = app('Laravel\Passport\\TokenRepository');
                //$tokenRepository->revokeAccessToken($jti);

                $userTokens = $user->tokens;
                foreach($userTokens as $token) {
                    $token->revoke();
                }

                /*DB::beginTransaction();

                try {
                    $oauthAccessToken = OauthAccessToken::where('id', $jti)->first();
                    $oauthAccessToken->revoked = true;
                    $oauthAccessToken->timestamps = false;
                    $oauthAccessToken->save();
                    $oauthRefreshToken = OauthRefreshToken::where('access_token_id', $jti)->first();
                    $oauthRefreshToken->revoked = true;
                    $oauthRefreshToken->timestamps = false;
                    $oauthRefreshToken->save();
                }catch (\Exception $e){
                    DB::rollBack();
                    return new DataOrError(null, [$e->getMessage()],true);
                }

                DB::commit();*/

                ProcessMessages::dispatch(env('USER_LOGED_OUT_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode(['jti' => $jti]));
                //Session::flush();
                //Auth::logout();
                return new DataOrError(__("Deconnecte avec succes"), null,false);
            }else{
                return new DataOrError(null, [__("Temoins invalide.")],true);
            }
        }catch (\Exception $e){
            return new DataOrError(null, [$e->getMessage()],true);

        }
    }

    public function isUserExists(Request $request, string $userid): DataOrError
    {
        try {
            if (Auth::guard('api')->check()) {
                $user = User::where('id', '=', $userid)->first();
                if ($user === null) {
                    return new DataOrError(false, null,false);
                }

                return new DataOrError(true, null,false);
            }else{
                return new DataOrError(null, [__("Temoins invalide.")],true);
            }
        }catch (\Exception $e){
            return new DataOrError(null, [$e->getMessage()],true);

        }

    }

    public function requestPasswordReset(Request $request): DataOrError
    {
        $validator = $this->domainServices->makePasswordResetInvitationValidator($request);
        if ($validator->fails()) {
            return new DataOrError(null, $validator->errors()->all(),true);
        }
        $tenant = Tenant::where('id', $request->get('tenant_id'))->where('active', true)->first();
        if ($tenant == null) {
            return new DataOrError(null, [__("Le marchand n'existe pas")],true);
        }
        $user = User::where('username', $request->get('username'))->where('tenant_id', $request->get('tenant_id'))->where('active', true)->first();
        if ($user === null) {
            return new DataOrError(null, [__("L'utilisateur n'existe pas")],true);
        }

        $passwordResetInvitation =
            PasswordResetInvitation::where('tenant_id', $tenant->id)
                ->where('username', $request->get('username'))
                ->where('email', $request->get('email'))
                ->where('active', true)
                ->where('used', false)->first();
        if ($passwordResetInvitation != null) {
            $url = $passwordResetInvitation->url;
            $passwordResetInvitation->email = $request->get('email');
            $passwordResetInvitation->expires_at = (Carbon::now()->addHours(24));

            $emaildata = [
                'email' => trim($request->get('email')),
                'name' => $user->full_name,
                'url' => $url,
                'tenant_name' => $tenant->name,
            ];

            ProcessSendEMailPasswordResetInvitationJob::dispatch($emaildata);
            $passwordResetInvitation->save();
            return new DataOrError(['invitation' => $passwordResetInvitation], null,false);
        }

        $invitation_id = Str::uuid()->toString();
        $url = env('ASSO_CLIENT_URL').'/users/' . $user->id . '/password-reset/' . $invitation_id;
        $invitationData = [
            'id' => $invitation_id,
            'tenant_id' => $tenant->id,
            'username' => $request->get('username'),
            'email' => $request->get('email'),
            'expires_at' => Carbon::now()->addHours(24),
            'used' => false,
            'active' => true,
            'state' => Constances::PENDING,
            'url' => $url
        ];

        $passwordReset = PasswordResetInvitation::create($invitationData);

        $emaildata = [
            'email' => trim($request->get('email')),
            'name' => $user->full_name,
            'url' => $url,
            'tenant_name' => $tenant->name,
        ];
        ProcessSendEMailPasswordResetInvitationJob::dispatch($emaildata);
        return new DataOrError(['invitation' => $passwordReset], null,false);
    }

    public function resetPassword(Request $request, string $passwordresetinvitationid)
    {
        $validator = $this->domainServices->makeResetPasswordValidator($request);
        if ($validator->fails()) {
            return new DataOrError(null, $validator->errors()->all(),true);
        }
        $passwordresetinvitation = PasswordResetInvitation::where('id', $passwordresetinvitationid)->first();
        if ($passwordresetinvitation == null) {
            return new DataOrError(null, [__("L'invitation n'existe pas")],true);
        }

        if ($passwordresetinvitation->isValidForUsage() === 1){
            return new DataOrError(null, [__("L'invitation desactivee")],true);
        }
        if ($passwordresetinvitation->isValidForUsage() === 2){
            return new DataOrError(null, [__("L'invitation expiree")],true);
        }
        if ($passwordresetinvitation->isValidForUsage() === 3){
            return new DataOrError(null, [__("L'invitation non en attente")],true);
        }
        if ($passwordresetinvitation->isValidForUsage() === 4){
            return new DataOrError(null, [__("L'invitation utilisee")],true);
        }

        $user = User::where('tenant_id', $passwordresetinvitation->tenant_id)
            ->where('username', $passwordresetinvitation->username)->first();
        if ($user == null) {
            return new DataOrError(null, [__("L'utilisateur n'existe pas")],true);
        }

        $user->password = Hash::make($request->get('password'));
        $user->save();
        return new DataOrError(['message' => __("Mot de passe modifie avec succes.")], null,false);
    }

    public function changePassword(Request $request, string $tenant_id, string $user_id)
    {
        $validator = $this->domainServices->makeChangePasswordValidator($request);
        if ($validator->fails()) {
            return new DataOrError(null, $validator->errors()->all(),true);
        }

        $tenant = Tenant::where('id', $tenant_id)->first();
        if ($tenant == null) {
            return new DataOrError(null, [__("Le marchand n'existe pas")],true);
        }
        $user = User::where('id', $user_id)->where('tenant_id', $tenant_id)->first();
        if ($user == null) {
            return new DataOrError(null, [__("L'utilisateur n'existe pas")],true);
        }
        if (Hash::check($request->get('current_password'), $user->password)) {
           $user->password = Hash::make($request->get('password'));
           $user->save();
            return new DataOrError(['message' => __("Mot de passe modifie avec succes.")], null,false);
        }
        return new DataOrError(null, [__("Mot de passe courant incorrect")],true);
    }

    public function changeUserState(Request $request, string $tenant_id, string $user_id, bool $state, ): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_TENANT", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $tenant = Tenant::where('id', $tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Marchand inconnu!")],true);
        }
        $user = User::where('id', $user_id)->where('tenant_id', $tenant_id)->first();

        $user->active = $state;
        $user->save();
        $dataOrError = new DataOrError(['user' => $user], null,false);

        ProcessMessages::dispatch(env('USER_STATE_CHANGED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        //Storage::disk('local')->makeDirectory('tenants/'.$id.'/logo');
        return $dataOrError;
    }

    public function updateUser(Request $request): DataOrError
    {
        $validatorUser = DomainServices::makeUpdateUserValidator($request);

        if ($validatorUser->fails()) {
            return new DataOrError(null, $validatorUser->errors()->all(),true);
        }

        $user = $request->user();
        $user->full_name = trim($request->get('full_name'));
        $user->phone_number = trim($request->get('phone_number'));
        if ($request->filled('email')) {
            $user->email = trim($request->get('email'));
        }
        if ($request->file('photo') != null) {
            $pathusrphoto = 'users/' . $user->id . '/photos';
            $photousrPath = $request->file('photo')->store($pathusrphoto, 'public');
            $oldPhotoPath = $user->photo;
            $user->photo = $photousrPath;
            if ($oldPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
            }
        }
        if ($request->filled('location')) {
            $user->location = trim($request->get('location'));
        }
        if ($request->filled('region')) {
            $user->region = trim($request->get('region'));
        }

        if($request->filled('country')){
            $country = country(strtolower($request->get('country')));
            $countryCode = $country->getIsoAlpha2();
            $user->country = $countryCode;
        }

        $user->save();

        //$user->password = '';

        $dataOrError = new DataOrError(['user' => $user], null,false);

        ProcessMessages::dispatch(env('USER_UPDATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));

        return $dataOrError;
    }

    public function setPhoto(Request $request, string $user_id)
    {
        $validatorImg = DomainServices::makeImageValidatorWithName($request, 'photo');
        if($validatorImg->fails()){
            return new DataOrError(null, $validatorImg->errors()->all(),true);
        }

        $user = User::where('id', $user_id)->first();
        if(!$user){
            return new DataOrError(null, [__("Utilisateur inconnu!")],true);
        }
        $pathuserphoto = 'users/'.$user_id.'/photos';
        $logoUserPath = $request->file('photo')->store($pathuserphoto, 'public');
        $oldPhotoPath = $user->photo;
        $user->photo = $logoUserPath;
        if ($oldPhotoPath) {
            Storage::disk('public')->delete($oldPhotoPath);
        }
        $user->save();
        return new DataOrError(['user' => $user], null,false);
    }

    public function getPhoto(Request $request, string $user_id)
    {
        $user = User::where('id', $user_id)->first();
        if(!$user){
            return new DataOrError(null, [__("Utilisateur inconnu!")],true);
        }
        //Log::info('$tenant->logo: '.$tenant->logo);
        //$content = Storage::disk('public')->get($tenant->logo);
        return new DataOrError(['content' => $user->photo], null,false);
    }

}
