<?php

namespace App\Domain\Model;

use App\Domain\DomainServices\DomainServices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model
{
    public const DEFAULT_TENANT_ID = "f733f002-53ec-4f82-aaa3-7374fc728b87";
    protected $table = 'tenants';
    protected $primaryKey = 'id';
    protected $casts = ['id' => 'string'];
    public $incrementing = false;

    protected $keyType = 'string';
    protected $fillable = [
          'id'
        , 'name'
        , 'city'
        , 'location'
        , 'region'
        , 'country'
        , 'logo'
        , 'phone_number'
        , 'website'
        , 'address'
        , 'active'
        , 'taxpayer_number'
        , 'number_trade_register'
        , 'license_id'
        , 'administrator'
        , 'network'
        , 'private_key_path'
        , 'public_key_path'
    ];

    /*public function isValidTenant(): bool
    {
        return $this->active;
    }*/

    public static function createDefault():Tenant{

        $tenantid = self::DEFAULT_TENANT_ID;

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
            throw new \Exception('Unable to save private key on sftp');
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
            throw new \Exception('Unable to generate private key on sftp');
        }
        if (file_exists($keysBaseDir)) {
            DomainServices::deleteDirectory($keysBaseDir);
            //unlink($keysBaseDir);
        }


        $tenantData = [
                'id' => $tenantid,
                'name' => "ASSO",
                'city' => "Douala",
                'location' => "Denver-Bonamoussadi",
                'region' => "Lottoral",
                'country' => "CM",
                //'logo' => $path,
                'phone_number' => "+237691179154",
                'website' => "https://polito.it",
                'address' => "Denver-Bonamoussadi",
                'active' => true,
                'taxpayer_number' => "M081914151580H",
                'number_trade_register' => "",
                'network' => json_encode(array()),
                'private_key_path' => $sftpPathToPrivateKey,
                'public_key_path' => $sftpPathToPublicKey,
            ];

        //dd($tenantData);
        return Tenant::create($tenantData);
    }
}
