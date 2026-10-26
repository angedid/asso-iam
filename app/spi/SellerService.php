<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use App\Domain\Model\Tenant;
use App\Jobs\ProcessMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SellerService
{

    protected DomainServices $domainServices;
    public function __construct(DomainServices $domainServices){
        $this->domainServices = $domainServices;
    }

    /**
     * @throws \Exception
     */
    public function registerSeller(Request $request): DataOrError
    {
        $validatorSeller = DomainServices::makeSellerValidator($request);
        if ($validatorSeller->fails()) {
            return new DataOrError(null, $validatorSeller->errors()->all(),true);
        }

        $sellerId = Str::uuid()->toString();

        $defaultTenant = Tenant::where('id', Tenant::DEFAULT_TENANT_ID)->first();

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $privateKey = openssl_pkey_new($config);
        Storage::disk('private')->makeDirectory($sellerId);
        $keysBaseDir = base_path() . '/storage/app/private/' . $sellerId;
        $pathToPrivateKey = $keysBaseDir . '/private.pem';
        $retVal = openssl_pkey_export_to_file($privateKey, $pathToPrivateKey);

        if (!$retVal) {
            throw new \Exception('Unable to generate private key localy');
        }

        $sftpBaseDir = 'home/security/' . $sellerId ;
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
            throw new \Exception('Unable to generate public key on sftp');
        }
        if (file_exists($keysBaseDir)) {
            //unlink($keysBaseDir);
            DomainServices::deleteDirectory($keysBaseDir);
        }

        $sellerData = [
            'id' => $sellerId,
            'tenant_id' => $defaultTenant->id,
            'type' => 'SELLER_USER',
            'full_name' => trim($request->get('full_name')),
            'username'=> trim($request->get('username')) . '@' . $defaultTenant->id,
            'local_username' => trim($request->get('username')),
            'email' => trim($request->get('email')),
            'password' => Hash::make($request->get('password')),
            'phone_number' => trim($request->get('phone_number')),
            'location' => trim($request->get('location')),
            'region' => trim($request->get('region')),
            'country' => trim($request->get('country')),
            'network' => json_encode([]),
            'active' => true,
            'private_key_path' => $sftpPathToPrivateKey,
            'public_key_path' => $sftpPathToPublicKey,
        ];
        $seller = User::create($sellerData);
        if ($request->file('photo') != null) {
            $pathsellerphoto = 'sellers/' . $sellerId . '/photos';
            $photosellerPath = $request->file('photo')->store($pathsellerphoto, 'public');
            $seller->photo = $photosellerPath;
        }
        if ($request->filled('country')) {
            $country = country(strtolower($request->get('country')));
            $countryCode = $country->getIsoAlpha2();
            $seller->country = $countryCode;
        }

        $seller->save();
        $seller->password = '';
        $dataOrError = new DataOrError(['seller' => $seller], null,false);
        ProcessMessages::dispatch(env('SELLER_REGISTERED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($seller));
        return $dataOrError;
    }
}
