<?php

namespace App\Domain\DomainServices;
use App\Domain\Model\AssoLicense;
use App\Domain\Model\Order;
use App\Domain\Model\Payment;
use App\Domain\Model\Role;
use App\Domain\Model\Tenant;
use App\Models\User;
use App\spi\DataOrError;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Jekk0\Laravel\Iso3166\Validation\Rules\Iso3166Alpha2;
use Nanorocks\LicenseManager\Models\License;
use Nanorocks\LicenseManager\Services\LicenseService;

class DomainServices
{
    public function __construct()
    {
    }

    /*
     * This part of the code handle the data validation incoming in the application
     */


    /**
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    public static function makeTenantsValidators(Request $request): \Illuminate\Validation\Validator
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'city' => 'required|string|min:2|max:255',
            'country' => ['required', new Iso3166Alpha2()],//'required|string|min:2|max:2|country',
            'phone_number' => 'required|phone|string|min:2|max:255',

        ], [
            'name.required' => __("La raison sociale est obligatoire."),
            'name.min' => __("La raison sociale doit avoir au moins 2 caractères."),
            'city.required' => __("La ville de l'activité est obligatoire."),
            'city.min' => __("La ville de l'activité doitavoir au moins 2 caractères."),
            'country.required' => __('Le pays est obligatoire.'),
            //'country.min' => __('Le code pays doit avoir 2 caractères.'),
            'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone_number.phone' => __('Numero de téléphone invalide.'),
        ]);

        if ($validator->fails()) {
            return $validator;
        }

        if ($request->filled('location')) {
            $validatorLocation = Validator::make($request->all(), [
                'location' => 'required|string|min:2|max:255',
            ], [
                'location.required' => __("La résidence est obligatoire."),
                'location.min' => __("La résidence doit avoir au moins 2 caractères."),
            ]);
            if ($validatorLocation->fails()) {
                return $validatorLocation;
            }
        }

        if ($request->filled('region')) {
            $validatorRegion = Validator::make($request->all(), [
                'region' => 'required|string|min:2|max:255',
            ], [
                'region.required' => __("La région est obligatoire."),
                'region.min' => __("La région doit avoir au moins 2 caractères."),
            ]);
            if ($validatorRegion->fails()) {
                return $validatorRegion;
            }
        }

        if ($request->file('logo')) {
            $validatorLogo = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240|dimensions:min_width=25,max_width=500,min_height=25,max_height=500',
            ], [
                'logo.required' => __("Le logo est obligatoire."),
                'logo.image' => __("Le logo doit être un fichier image."),
                'logo.mimes' => __("Les formats attendus sont jpeg, png, jpg, gif, svg."),
                'logo.dimensions' => __("La largeur et la longueur sont comprises entre 25 et 500 pixels."),
            ]);
            if ($validatorLogo->fails()) {
                return $validatorLogo;
            }
        }

        if ($request->filled('website')) {
            $validatorWebsite = Validator::make($request->all(), [
                'website' => 'required|string|min:13|url:http,https',
            ], [
                'website.required' => __("Le site web est obligatoire."),
                'website.min' => __("Le site web doit avoir au moins 13 caractères."),
                'website.url' => __("Url invalide."),
            ]);
            if ($validatorWebsite->fails()) {
                return $validatorWebsite;
            }
        }

        if ($request->filled('address')) {
            $validatorAddress = Validator::make($request->all(), [
                'address' => 'required|string|min:2|max:255',
            ], [
                'address.required' => __("L'adresse est obligatoire."),
                'address.min' => __("L'adresse doit avoir au moins 2 caractères."),
            ]);
            if ($validatorAddress->fails()) {
                return $validatorAddress;
            }
        }

        if ($request->filled('taxpayer_number')) {
            $validatorTpn = Validator::make($request->all(), [
                'taxpayer_number' => 'required|string|min:2|max:255',
            ], [
                'taxpayer_number.required' => __("Le numéro de contribuable est obligatoire."),
                'taxpayer_number.min' => __("Le numéro de contribuable doit avoir au moins 2 caractères."),
            ]);
            if ($validatorTpn->fails()) {
                return $validatorTpn;
            }
        }

        if ($request->filled('number_trade_register')) {
            $validatorTnr = Validator::make($request->all(), [
                'number_trade_register' => 'required|string|min:2|max:255',
            ], [
                'number_trade_register.required' => __("Le régistre de commerce est obligatoire."),
                'number_trade_register.min' => __("Le régistre de commerce doit avoir au moins 2 caractères."),
            ]);
            if ($validatorTnr->fails()) {
                return $validatorTnr;
            }
        }

        return $validator;
    }

    public static function makeTenantValidator(Request $request){
        return self::makeTenantsValidators($request);
    }

    public static function makeUpdateTenantValidator(Request $request){
        return self::makeTenantsValidators($request);
    }

    public static function makeUserValidatorForTenantProvision(Request $request){
        $validator =  Validator::make($request->all(), [
            'full_name' => 'required|string|min:2|max:255',
            'username' => 'required|string|min:2|max:255|unique:users,username',
            'phone_number' => 'required|phone|string|min:2|max:255',
            'password' => ['required', 'string', 'max:20',Password::min(8)
                                                                ->letters()
                                                                ->mixedCase()
                                                                ->numbers()
                                                                ->symbols()
                                                                ->uncompromised(),
                                                                'confirmed'],

        ],[
            'full_name.required' => __("Le nom complet est obligatoire."),
            'full_name.min' => __("Le nom complet doit avoir au moins 2 caractères."),
            'username.required' => __("Le nom d'utilisateur est obligatoire."),
            'username.min' => __("Le nom d'utilisateur doit avoir au moins 2 caractères."),
            'username.unique' => __("Nom d'utilisateur déja utilisé."),
            'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone_number.phone' => __('Numero de téléphone invalide.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),
        ]);


        return self::controlOptionalParams($validator, $request);
    }

    public static function makeUserValidator(Request $request){
        $validator =  Validator::make($request->all(), [
            'full_name' => 'required|string|min:2|max:255',
            'username' => 'required|string|min:2|max:255|unique:users,username',
            'phone_number' => 'required|phone',
            'password' => ['required', 'string', 'max:20',Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
                'confirmed'],
            'invitation_id' => 'required|string|exists:users_registration_invitations,id',

        ],[
            'full_name.required' => __("Le nom complet est obligatoire."),
            'full_name.min' => __("Le nom complet doit avoir au moins 2 caractères."),
            'username.required' => __("Le nom d'utilisateur est obligatoire."),
            'username.min' => __("Le nom d'utilisateur doit avoir au moins 2 caractères."),
            'username.unique' => __("Nom d'utilisateur déja utilisé."),
            'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone_number.phone' => __('Numero de téléphone invalide.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),
            'invitation_id.required' => __("L'invitation est obligatoire."),
            'invitation_id.exists' => __("Invitation introuvable."),
        ]);


        return self::controlOptionalParams($validator, $request);
    }

    public static function makeUpdateUserValidator(Request $request){
        $validator =  Validator::make($request->all(), [
            'full_name' => 'required|string|min:2|max:255',
            'phone_number' => 'required|phone|string|min:2|max:255',
        ],[
            'full_name.required' => __("Le nom complet est obligatoire."),
            'full_name.min' => __("Le nom complet doit avoir au moins 2 caractères."),
            'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone_number.phone' => __('Numero de téléphone invalide.'),
        ]);

        return self::controlOptionalParams($validator, $request);
    }

    public static function makeSellerValidator(Request $request){
        $validator =  Validator::make($request->all(), [
            'full_name'      => 'required|string|min:2|max:255',
            'username'       => 'required|string|min:2|max:255|unique:users,username',
            //'email'          => 'required|string|email|max:255|unique:sellers,email',
            'phone_number'   => 'required|phone|string|min:2|max:255',
            'password'       => ['required', 'string', 'max:20',Password::min(8)
                                ->letters()
                                ->mixedCase()
                                ->numbers()
                                ->symbols()
                                ->uncompromised(), 'confirmed'],

        ],[
            'full_name.required' => __("Le nom complet est obligatoire."),
            'full_name.min' => __("Le nom complet doit avoir au moins 2 caractères."),
            'username.required' => __("Le nom d'utilisateur est obligatoire."),
            'username.min' => __("Le nom d'utilisateur doit avoir au moins 2 caractères."),
            'username.unique' => __("Nom d'utilisateur déja utilisé."),
            //'email.unique' => __("Email déja utilisé."),
            //'email.email' => __("Email invalide."),
            'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone_number.phone' => __('Numero de téléphone invalide.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),

        ]);


        if($validator->fails()){
            return $validator;
        }

        if ($request->filled('email')) {
            $validatorEmail = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
            ],[
                'email.required' => __("L'adresse E-Mail est obligatoire"),
                'email.email' => __("L'adresse E-Mail n'est pas valide"),
                'email.unique' => __("Email déja utilisé."),
            ]);
            if($validatorEmail->fails()){
                return $validatorEmail;
            }
        }

        return self::makeValidatePhotoLocationAndRegion($request, $validator);
    }

    public static function makeRoleValidator(Request $request){
        return Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'description' => 'required|string|min:10|max:5000',
            'tenant_id' => 'required|string|exists:tenants,id',
        ],[
            'name.required' => __("Le nom est obligatoire."),
            'name.min' => __("Le nom doit avoir au moins 2 caractères."),
            //'name.unique' => __("Nom déjà utilisé."),
            'tenant_id.required' => __("L'entreprise est obligatoire."),
            'tenant_id.exists' => __("Entreprise non reconnu."),
            'description.required' => __("La description est obligatoire."),
            'description.min' => __("La description doit avoir au moins 10 caractères."),
        ]);
    }

    public static function makeScopeValidator(Request $request){
        return  Validator::make($request->all(), [
            'key' => 'required|string|min:2|max:255|unique:scope,key',
            'description' => 'required|string|min:10|max:5000',
        ],[
            'key.required' => __('La clé est obligatoire.'),
            'key.min' => __("La clé doit avoir au moins 2 caractères."),
            'key.unique' => __("Clé déjà utilisée."),
            'description.required' => __("La description est obligatoire."),
            'description.min' => __("La description doit avoir au moins 2 caractères."),
        ]);
    }

    public static function makeGroupValidator(Request $request){
        return  Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'description' => 'required|string|min:10|max:5000',
        ],[
            'name.required' => __("Le nom est obligatoire."),
            'name.min' => __("Le nom doit avoir au moins 2 caractères."),
            'description.required' => __("La description est obligatoire."),
            'description.min' => __("La description doit avoir au moins 10 caractères."),
        ]);
    }

    public static function makePasswordResetInvitationValidator(Request $request){
        return Validator::make(
            $request->all(),
            [
                'tenant_id' => 'required|string|exists:tenants,id',
                'email' => 'required|string|email',
                'username' => 'required|string|exists:users,username',
            ],

            [
                'tenant_id.required' => __('Le marchand est obligatoire'),
                'tenant_id.exists' => __('Le marchand n\'existe pas'),
                'username.required' => __('Le nom d\'utilisateur est obligatoire'),
                'username.exists' => __('Le nom d\'utilisateur n\'existe pas'),
                'email.required' => __('L\'adresse email est obligatoire'),
                'email.email' => __('L\'adresse email n\'est pas valide'),
            ]
        );
    }

    public static function makeResetPasswordValidator(Request $request){
        return  Validator::make($request->all(), [

            'password'       => [
                'required',
                'string',
                'max:20',
                Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
                'confirmed'
            ],

        ],[
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),

        ]);
    }

    public static function makeChangePasswordValidator(Request $request){
        return  Validator::make($request->all(), [
            'current_password'       => [
                'required',
                'string',
                'max:20',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),

            ],
            'password'       => [
                'required',
                'string',
                'max:20',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed'
            ],

        ],[
            'current_password.required' => __('Le mot de passe est obligatoire.'),
            'current_password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'current_password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'current_password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'current_password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'current_password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),

        ]);
    }


    public static function makeUserRegistrationInvitationValidator(Request $request){
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|min:2|max:255',
            'email' => 'required|string|min:2|max:255|email',
            'tenant_id' => 'required|exists:tenants,id',
            'invited_by' => 'required|exists:users,id',

        ],[
            'full_name.required' => __("Le nom complet est obligatoire."),
            'full_name.min' => __("Le nom complet doit avoir au moins 2 caractères."),
            'email.required' => __("L'adresse E-Mail est obligatoire"),
            'email.email' => __("L'adresse E-Mail n'est pas valide"),
            'tenant_id.required' => __("Le tenant est obligatoire."),
            'tenant_id.exists' => __("Tenant non reconnu."),
            'invited_by.required' => __("L'inviteur est obligatoire."),
            'invited_by.exists' => __("Inviteur non reconnu."),
        ]);

        if($validator->fails()){
            return $validator;
        }

        if ($request->filled('phone_number')) {
            $validatorPhonenumber = Validator::make($request->all(), [
                'phone_number'   => 'required|phone|string|min:2|max:255',
            ],[
                'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
                'phone_number.phone' => __('Numero de téléphone invalide.'),
            ]);
            if($validatorPhonenumber->fails()){
                return $validatorPhonenumber;
            }
        }


        return $validator;
    }

    public static function makeImageValidator(Request $request){
        return Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240|dimensions:min_width=25,max_width=500,min_height=25,max_height=500',
        ],[
            'logo.required' => __("Le logo est obligatoire."),
            'logo.image' => __("Le logo doit être un fichier image."),
            'logo.mimes' => __("Les formats attendus sont jpeg, png, jpg, gif, svg."),
            'logo.dimensions' => __("La largeur et la longueur sont comprises entre 25 et 500 pixels."),
        ]);

    }

    public static function makeImageValidatorWithName(Request $request, string $name){
        return Validator::make($request->all(), [
            $name => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240|dimensions:min_width=25,max_width=500,min_height=25,max_height=500',
        ],[
            $name.'.required' => __("Le logo est obligatoire."),
            $name.'.image' => __("Le logo doit être un fichier image."),
            $name.'.mimes' => __("Les formats attendus sont jpeg, png, jpg, gif, svg."),
            $name.'.dimensions' => __("La largeur et la longueur sont comprises entre 25 et 500 pixels."),
        ]);

    }

    /**
     * @param \Illuminate\Validation\Validator $validator
     * @param Request $request
     * @return \Illuminate\Validation\Validator
     */
    public static function controlOptionalParams(\Illuminate\Validation\Validator $validator, Request $request): \Illuminate\Validation\Validator
    {
        if ($validator->fails()) {
            return $validator;
        }

        if ($request->filled('email')) {
            $validatorEmail = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
            ], [
                'email.required' => __("L'adresse E-Mail est obligatoire"),
                'email.email' => __("L'adresse E-Mail n'est pas valide"),
            ]);
            if ($validatorEmail->fails()) {
                return $validatorEmail;
            }
        }

        return self::makeValidatePhotoLocationAndRegion($request, $validator);
    }

    /**
     * @param Request $request
     * @param \Illuminate\Validation\Validator $validator
     * @return \Illuminate\Validation\Validator
     */
    public static function makeValidatePhotoLocationAndRegion(Request $request, \Illuminate\Validation\Validator $validator): \Illuminate\Validation\Validator
    {
        if ($request->file('photo')) {
            $validatorPhoto = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240|dimensions:min_width=25,max_width=500,min_height=25,max_height=500',
            ], [
                'photo.required' => __("La photo est obligatoire."),
                'photo.image' => __("La photo doit être un fichier image."),
                'photo.mimes' => __("Les formats attendus sont jpeg, png, jpg, gif, svg."),
                'photo.dimensions' => __("La largeur et la longueur sont comprises entre 25 et 500 pixels."),
            ]);
            if ($validatorPhoto->fails()) {
                return $validatorPhoto;
            }
        }

        if ($request->filled('location')) {
            $validatorLocation = Validator::make($request->all(), [
                'location' => 'required|string|min:2|max:255',
            ], [
                'location.required' => __("La résidence est obligatoire."),
                'location.min' => __("La résidence doit avoir au moins 2 caractères."),
            ]);
            if ($validatorLocation->fails()) {
                return $validatorLocation;
            }
        }

        if ($request->filled('region')) {
            $validatorRegion = Validator::make($request->all(), [
                'region' => 'required|string|min:2|max:255',
            ], [
                'region.required' => __("La région est obligatoire."),
                'region.min' => __("La région doit avoir au moins 2 caractères."),
            ]);
            if ($validatorRegion->fails()) {
                return $validatorRegion;
            }
        }

        if ($request->filled('country')) {
            $validatorCountry = Validator::make($request->all(), [
                'country' => ['required', new Iso3166Alpha2()],
            ], [
                'country.required' => __('Le pays est obligatoire.'),
            ]);
            if ($validatorCountry->fails()) {
                return $validatorCountry;
            }
        }

        return $validator;
    }

    public static function makeSuperAdminValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email|max:255',
            'username' => 'required|string|min:2|max:255|unique:users,username',
            'phone_number' => 'required|phone|string|min:2|max:255',
            'password' => ['required', 'string', 'max:20',Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
                'confirmed'],

        ],[
            'name.required' => __("Le nom complet est obligatoire."),
            'name.min' => __("Le nom complet doit avoir au moins 2 caractères."),
            'email.required' => __("L'adresse E-Mail est obligatoire."),
            'email.email' => __("L'adresse E-Mail n'est pas valide"),
            'username.required' => __("Le nom d'utilisateur est obligatoire."),
            'username.min' => __("Le nom d'utilisateur doit avoir au moins 2 caractères."),
            'username.unique' => __("Nom d'utilisateur déja utilisé."),
            'phone_number.required' => __('Le numéro de téléphone est obligatoire.'),
            'phone_number.phone' => __('Numero de téléphone invalide.'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),
        ]);
    }

    public static  function makeChangePasswordFirstConnectionValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'userid' => 'required|exists:super_admins,id',
            'password' => ['required', 'string', 'max:20',Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
                'confirmed']
        ],[
            'userid.required' => __('Utilisateur non reconnu!'),
            'userid.exists' => __('Utilisateur non reconnu!'),
            'password.required' => __('Le mot de passe est obligatoire.'),
            'password.min' => __('Le mot de passe doit avoir au moins 8 caractères'),
            'password.max' => __('Le mot de passe doit avoir au plus 20 caractères'),
            'password.confirmed' => __('Les mots de passe ne correspondent pas'),
            'password.mixed' => __('Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux.'),
            'password.symbols' => __("Le mot de passe doit etre composé de caractères alphanumériques, et des caractères spéciaux."),
            'password.letters' => __("Le mot de passe doit avoir au moins une lettre majuscule et une lettre minuscule"),
        ]);
    }

    public static function makeSuperAdminCredentialsValidator(Request $request)
    {
        return Validator::make($request->all(), [
            'username' => ['required', 'string', 'exists:super_admins,username'],
            'password' => ['required', 'string', 'max:20',Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()]
        ],[
            'username.exists' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'username.required' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'password.required' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'password.min' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'password.max' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'password.mixed' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'password.symbols' => __("Nom d'utilisateur ou mot de passe incorrect."),
            'password.letters' => __("Nom d'utilisateur ou mot de passe incorrect."),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Part that handle operations that operate on more than one agregate
    |--------------------------------------------------------------------------
    |
    |
    */

    public static function  retrieveScopes(Request $request): array
    {
        $user = $request->user();
        if ($user->tenant_id !== null) {
            $roles = Role::where("tenant_id", $user->tenant_id)->get();
        }else{
            $roles = Role::all();
        }

        $scopes = [];
        foreach ($roles as $role) {
            if ($role->isUserPlayingRole($user->id)) {
                $roleScopes = json_decode($role->scopes, true);
                for($i = 0; $i < count($roleScopes); $i++){
                    $scopes[] = $roleScopes[$i];
                }
            }
        }
        return $scopes;
    }
    public static function  isValidTenant(Tenant $tenant, License $license, User $owner): bool{
        if ($tenant === null) {
            return false;
        }
        if ($tenant->active === false){
            return false;
        }
        if ($owner === null){
            return false;
        }
        if ($owner->active === false){
            return false;
        }
        if($license === null){
            return false;
        }
        if ($license->active === false){
            return false;
        }
        if ($license->tenant_id !== $tenant->id){
            return false;
        }
        $service = app(LicenseService::class);
        return $service->validateLicense($license->license_key);
    }
    public static function generateLicenseKey():string
    {
        $numberFormated = null;
        do {
            $number = random_int(100000000000, 999999999999);
            $numberStr = (string) $number;
            $numberFormated = implode("-", str_split($numberStr, 4));
        } while (License::where("license_key", "=", $numberFormated)->first());

        return $numberFormated;
    }

    public static function generateLicense(User $user, Payment $payment) : AssoLicense
    {
        $orderId = $payment->order_number;
        $order = Order::where("id", $orderId)->first();
        Log::info("ODERS: " . json_encode($order));
        $tenant = Tenant::where("id", $user->tenant_id)->first();
        $users = User::where("tenant_id", $tenant->id)->get();
        $orderItems = json_decode($order->items);
        $licenseType = $orderItems[0];
        Log::info("LICENSE TYPE: " . json_encode($licenseType));
        Log::info("number_of_days: " . strval($licenseType->number_of_days));
        $expires_at = Carbon::now()->addDays($licenseType->number_of_days);
        $licenseData = [
            'license_key' => self::generateLicenseKey(),
            'assigned_to' => $user->id,
            'tenant_id' => $tenant->id,
            'is_trial' => $licenseType->name === "TRIAL",
            'users' => json_encode($users),
            'active' => true,
            'expires_at' => $expires_at,
            'metadata' => json_encode([]),
            'duration' => $licenseType->number_of_days,
            'amount' => $payment->amount,
            'order_id' => $order->id,
        ];
        //Log::info("Items: " + $order->items);
        return  AssoLicense::create($licenseData);//$service->createLicense($licenseData);
    }

    public function getLicenceDetails(Request $request, string $tenant_id) : DataOrError
    {
        $tenant = Tenant::where("id", $tenant_id)->first();
        if ($tenant === null) {
            return new DataOrError(null, [__("Tenant not found!")],true);
        }

        $licenseDetails = [];
        $assoLicenses = AssoLicense::where("tenant_id", $tenant_id)->where('expires_at', '>', (Carbon::now())->addMinutes(2))->get();
        foreach ($assoLicenses as $assoLicense) {
            $user = User::where("id", $assoLicense->assigned_to)->first();
            if ($user === null) {
                return new DataOrError(null, [__("User not found!")],true);
            }
            if (self::isValidTenant($tenant, $assoLicense, $user)) {
                $order_id = $assoLicense->order_id;
                $order = Order::where("id", $order_id)->first();
                $payment = Payment::where('order_number', $order_id)->where('status', Payment::SUCCESS)->first();
                $licenseDetails[] = ['license'=>$assoLicense, 'order'=>$order, 'payment'=>$payment];
            }
        }
        return new DataOrError($licenseDetails, null,false);
    }
}
