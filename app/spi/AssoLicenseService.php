<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\DataOrError;
use Illuminate\Http\Request;

class AssoLicenseService
{
    protected DomainServices $domainServices;
    public function __construct(DomainServices $domainServices){
        $this->domainServices = $domainServices;
    }

    public function getLicenceDetails(Request $request, string $tenant_id):DataOrError
    {
        return $this->domainServices->getLicenceDetails($request, $tenant_id);
    }
}
