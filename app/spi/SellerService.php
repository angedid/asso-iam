<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\Tenant;
use App\Jobs\ProcessMessages;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SellerService
{

    protected DomainServices $domainServices;
    public function __construct(DomainServices $domainServices){
        $this->domainServices = $domainServices;
    }

    public function registerSeller(Request $request): DataOrError
    {
        $validatorSeller = DomainServices::makeSellerValidator($request);
        if ($validatorSeller->fails()) {
            return new DataOrError(null, $validatorSeller->errors()->all(),true);
        }

        $sellerId = Str::uuid()->toString();

        $defaultTenant = Tenant::where('id', Tenant::DEFAULT_TENANT_ID)->first();

        $sellerData = [
            'id' => $sellerId,
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
        ProcessMessages::dispatch(env('SELLER_REGISTERED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($dataOrError));
        return $dataOrError;
    }
}
