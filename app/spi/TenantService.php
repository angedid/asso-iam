<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use App\Domain\Model\Role;
use App\Domain\Model\Tenant;
use App\Jobs\ProcessMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenantService
{
    protected DomainServices $domainServices;
    public function __construct(DomainServices $domainServices){
        $this->domainServices = $domainServices;
    }
    public function getTenants(Request $request): DataOrError
    {
        $tenants = [];
         foreach (Tenant::all() as $tenant) {
             $tenants[] = $tenant;
         }

         return new DataOrError(['tenants' => $tenants], null,false);
    }

    public function retrieveTenantById(Request $request, string $tenant_id)
    {
        $tenant = Tenant::where('id', $tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Grossiste inconnu!")],true);
        }
        $owner = User::where('id', $tenant->owner)->first();
        return new DataOrError(['tenant' => $tenant, 'owner' => $owner], null,false);
    }


    public function provisionTenant(Request $request): DataOrError
    {
        $validatorTenant = DomainServices::makeTenantValidator($request);
        if ($validatorTenant->fails()) {
            return new DataOrError(null, $validatorTenant->errors()->all(),true);
        }

        $validatorUser = DomainServices::makeUserValidatorForTenantProvision($request);
        if ($validatorUser->fails()) {
            return new DataOrError(null, $validatorUser->errors()->all(),true);
        }

        $tenantid = Str::uuid()->toString();
        $country = country(strtolower($request->get('country')));
        $countryCode = $country->getIsoAlpha2();

        //Log::info('REQUEST_DEBUGGING : {req}', ['req' => $request->all()]);

        DB::beginTransaction();

        try {

            $existTenant = Tenant::where('name', $request->get('name'))->where('country', $countryCode)->first();
            if ($existTenant !== null) {
                return new DataOrError(null, [__("Le nom " . $request->get('name') . " n'est pas disponible.")],true);
            }

            $config = [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            $privateKey = openssl_pkey_new($config);
            Storage::disk('private')->makeDirectory($tenantid);
            $keysBaseDir = base_path() . '/storage/app/private/' . $tenantid;
            $pathToPrivateKey = $keysBaseDir . '/private.pem';
            $retVal = openssl_pkey_export_to_file($privateKey, $pathToPrivateKey);

            if (!$retVal) {
                throw new \Exception('Unable to generate private key localy');
            }

            $sftpBaseDir = 'home/security/' . $tenantid ;
            $sftpPathToPrivateKey = $sftpBaseDir . '/private.pem';

            $retVal = Storage::disk('sftp')->put($sftpPathToPrivateKey, file_get_contents($pathToPrivateKey));

            if (!$retVal) {
                throw new \Exception('Unable to generate private key on sftp');
            }

            if (file_exists($pathToPrivateKey)) {
                unlink($pathToPrivateKey);
            }

            $publicKeyDetails = openssl_pkey_get_details($privateKey);
            $publicKey = $publicKeyDetails['key'];
            //throw new \Exception($publicKey);
            $pathToPublicKey = $keysBaseDir . '/public.pem';
            $fh = fopen($pathToPublicKey, 'w');
            fwrite($fh, $publicKey);
            fclose($fh);

            $sftpPathToPublicKey = $sftpBaseDir . '/public.pem';

            $retVal = Storage::disk('sftp')->put($sftpPathToPublicKey, file_get_contents($pathToPublicKey));
            if (!$retVal) {
                throw new \Exception('Unable to read private key on sftp');
            }
            if (file_exists($keysBaseDir)) {
                DomainServices::deleteDirectory($keysBaseDir);
                //unlink($keysBaseDir);
            }
            /*$retVal2 = openssl_pkey_export_to_file($publicKey, $pathToPublicKey);
            if (!$retVal2) {
                throw new \Exception('Unable to generate public key');
            }*/

            $tenantData = [
                'id' => $tenantid,
                'name' => trim($request->input('name')),
                'city' => trim($request->input('city')),
                'location' => trim($request->input('location')),
                'region' => trim($request->input('region')),
                'country' => $countryCode,
                //'logo' => $path,
                'phone_number' => trim($request->input('phone_number')),
                'website' => trim($request->input('website')),
                'address' => trim($request->input('address')),
                'active' => true,
                'taxpayer_number' => trim($request->input('taxpayer_number')),
                'number_trade_register' => trim($request->input('number_trade_register')),
                'network' => json_encode(array()),
                'private_key_path' => $sftpPathToPrivateKey,
                'public_key_path' => $sftpPathToPublicKey,
            ];
            //Log::info('Tenant Data : {tntdata}', ['tntdata' => $tenantData]);

            $tenant = Tenant::create($tenantData);

            $userid = Str::uuid()->toString();

            /*$email = null;
            if ($request->filled('email')) {
                $email = trim($request->get('email'));
            }*/

            //type::text = ANY (ARRAY['MERCHANT_USER'::character varying, 'SELLER_USER'::character varying]::text[])

            $username = trim($request->get('username'));
            $userData = [
                'id' => $userid,
                'tenant_id' => $tenant->id,
                'type' => 'MERCHANT_ADMINISTRATOR',
                'full_name' => trim($request->get('full_name')),
                'username' => $username.'@'.$tenant->id,
                'local_username' => $username,
                'email' => trim($request->get('email')),
                'password' => Hash::make($request->get('password')),
                'phone_number' => trim($request->get('phone_number')),
                'location' => trim($request->get('location')),
                'region' => trim($request->get('region')),
                'country' => $countryCode,
                'active' => true,
                'network' => json_encode([]),
                'private_key_path' => $sftpPathToPrivateKey,
                'public_key_path' => $sftpPathToPublicKey,
            ];

            $user = User::create($userData);

            //$user->local_username = $username;
            //$user->save();

            //Log::info('Tenant Data : {userdata}', ['userdata' => $userData]);
            $tenantAdminRole = Role::create([
                'id' => Str::uuid()->toString(),
                'tenant_id' => $tenant->id,
                'name' => 'Tenant Administrator',
                'description' => 'Tenant Administrator',
                'groups_playing_role' => json_encode(array()),
                'users_playing_role' => json_encode([$user->id]),
                'scopes' => env('TENANT_ADMINS_SCOPES'),
                'active' => true,
            ]);

            //Log::info('111111111111111111');

            $userRole = Role::create([
                'id' => Str::uuid()->toString(),
                'tenant_id' => $tenant->id,
                'name' => 'Tenant User',
                'description' => 'Tenant User',
                'groups_playing_role' => json_encode(array()),
                'users_playing_role' => json_encode([$user->id]),
                'scopes' => env('TENANT_USERS_SCOPES'),
                'active' => true,
            ]);

            //Log::info('2222222222222222222222');

            //Log::info('Tenant Data : {userRole}', ['userRole' => $userRole]);
            if ($request->file('logo') != null){
                $pathtntlogo = 'tenants/'.$tenantid.'/logos';
                $logoTntPath = $request->file('logo')->store($pathtntlogo, 'public');
                $tenant->logo = $logoTntPath;
            }

            if ($request->file('photo') != null) {
                $pathusrphoto = 'users/' . $userid . '/photos';
                $photousrPath = $request->file('photo')->store($pathusrphoto, 'public');
                $user->photo = $photousrPath;
            }
            $tenant->owner = $user->id;
            $tenant->save();
            $user->save();
            //Log::info('REQUEST_DEBUGGING : {tenant}', ['tenant' => $tenant]);
        }catch (\Exception $exception){
            DB::rollBack();
            return new DataOrError(null, [$exception->getMessage()],true);
        }
        DB::commit();
        /// TODO publis to the queue
        $user->password = '';
        $dataOrError = new DataOrError(['tenant' => $tenant, 'user' => $user,'role_tenant_admin'=>$tenantAdminRole, 'role_tenant_user' => $userRole], null,false);

        ProcessMessages::dispatch(env('TENANT_SSL_PROVISIONED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        //Storage::disk('local')->makeDirectory('tenants/'.$id.'/logo');
        return $dataOrError;
    }

    public function deactivateTenant(Request $request, string $tenant_id)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_TENANT", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $tenant = Tenant::where('id', $tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Marchand inconnu!")],true);
        }
        $tenant->active = false;
        $tenant->save();
        $dataOrError = new DataOrError(['tenant' => $tenant], null,false);

        ProcessMessages::dispatch(env('TENANT_DEACTIVATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        //Storage::disk('local')->makeDirectory('tenants/'.$id.'/logo');
        return $dataOrError;
    }

    public function activateTenant(Request $request, string $tenant_id)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_TENANT", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }

        $tenant = Tenant::where('id', $tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Marchand inconnu!")],true);
        }
        $tenant->active = true;
        $tenant->save();
        $dataOrError = new DataOrError(['tenant' => $tenant], null,false);

        ProcessMessages::dispatch(env('TENANT_ACTIVATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        //Storage::disk('local')->makeDirectory('tenants/'.$id.'/logo');
        return $dataOrError;
    }



    public function setLogo(Request $request, string $tenant_id)
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_TENANT", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $validatorImg = DomainServices::makeImageValidator($request);
        if($validatorImg->fails()){
            return new DataOrError(null, $validatorImg->errors()->all(),true);
        }

        $tenant = Tenant::where('id', $tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Marchand inconnu!")],true);
        }
        $pathtntlogo = 'tenants/'.$tenant_id.'/logos';
        $logoTntPath = $request->file('logo')->store($pathtntlogo, 'public');
        $oldLogoPath = $tenant->logo;
        $tenant->logo = $logoTntPath;
        if ($oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }
        $tenant->save();

        return new DataOrError(['tenant' => $tenant], null,false);
    }

    public function getLogo(Request $request, string $tenant_id)
    {
        $tenant = Tenant::where('id', $tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Marchand inconnu!")],true);
        }
        //Log::info('$tenant->logo: '.$tenant->logo);
        //$content = Storage::disk('public')->get($tenant->logo);
        return new DataOrError(['content' => $tenant->logo], null,false);
    }

    public function updateTenant(Request $request): DataOrError
    {
        $scopes = DomainServices::retrieveScopes($request);
        if(!in_array("SCOPE_MANAGE_TENANT", $scopes)){
            return new DataOrError(null, [__("Vous n'etes pas autorise!")],true);
        }
        $validator = DomainServices::makeUpdateTenantValidator($request);
        if($validator->fails()){
            return new DataOrError(null, $validator->errors()->all(),true);
        }

        $user = $request->user();

        $tenant = Tenant::where('id', $user->tenant_id)->first();
        if(!$tenant){
            return new DataOrError(null, [__("Marchand inconnu!")],true);
        }

        $tenant->name = trim($request->get('name'));
        $tenant->city = trim($request->get('city'));
        $country = country(strtolower($request->get('country')));
        $countryCode = $country->getIsoAlpha2();
        $tenant->country = $countryCode;
        $tenant->phone_number = trim($request->get('phone_number'));
        if ($request->filled('location')) {
            $tenant->location = trim($request->get('location'));
        }
        if ($request->filled('region')) {
            $tenant->region = trim($request->get('region'));
        }

        if ($request->file('logo') != null) {
            $pathtntlogo = 'tenants/' . $tenant->id . '/logos';
            $logoTntPath = $request->file('photo')->store($pathtntlogo, 'public');
            $oldLogoPath = $tenant->photo;
            $tenant->logo = $logoTntPath;
            if ($oldLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }
        }
        if ($request->filled('website')) {
            $tenant->website = trim($request->get('website'));
        }
        if ($request->filled('address')) {
            $tenant->address = trim($request->get('address'));
        }
        if ($request->filled('taxpayer_number')) {
            $tenant->taxpayer_number = trim($request->get('taxpayer_number'));
        }
        if ($request->filled('number_trade_register')) {
            $tenant->number_trade_register = trim($request->get('number_trade_register'));
        }
        $tenant->save();

        return new DataOrError(['tenant' => $tenant], null,false);
    }


}
