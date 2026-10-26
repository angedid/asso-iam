<?php

namespace App\Http\Controllers\Auth;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\Role;
use App\Domain\Model\SuperAdmin;
use App\Domain\Model\SuperAdminFirstTimeConnection;
use App\Domain\Model\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\spi\SellerService;
use App\spi\TenantService;
use App\spi\UserService;
use App\spi\UsersRegistrationsInvitationsService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;
    public TenantService $tenantService;
    public SellerService $sellerService;
    public UsersRegistrationsInvitationsService $usersRegistrationsInvitationsService;
    public UserService $userService;


    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    //protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('guest');
        $domainService= new DomainServices();
        $this->tenantService = new TenantService($domainService);
        $this->sellerService = new SellerService($domainService);
        $this->usersRegistrationsInvitationsService = new UsersRegistrationsInvitationsService($domainService);
        $this->userService = new UserService($domainService);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    /*protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }*/

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    /*protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }*/

    public function provisionTenant(Request $request){
        $resultReturned = $this->tenantService->provisionTenant($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function registerSeller(Request $request){

        $resultReturned = $this->sellerService->registerSeller($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function inviteUser(Request $request){
        //Log::info("INVITE USER=" . json_encode($request->all()));
        $resultReturned = $this->usersRegistrationsInvitationsService->inviteUser($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function registerUser(Request $request){
        $resultReturned = $this->userService->registerUser($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function requestPasswordReset(Request $request){
        $resultReturned = $this->userService->requestPasswordReset($request);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }

    public function resetPassword(Request $request, string $passwordresetinvitationid)
    {
        $resultReturned = $this->userService->resetPassword($request, $passwordresetinvitationid);
        if($resultReturned->isError){
            return response(array('success' => 0, 'faillure' => 1, 'raison' => $resultReturned->errors), 200);
        }
        return response(array('success' => 1, 'faillure' => 0, 'response' => $resultReturned), 200);
    }




    public function getAccessToken(Request $request)
    {
        //dd($request->all());

        //Log::info("Request: " . json_encode($request->all()));
        $code = $request->request->get('code');
        //$code = OauthAuthCode::where('id', $code_id)
        //Log::info("Code: $code");


        $response = Http::timeout(120)->asForm()->post(url('oauth/token'), [
            'grant_type' => $request->get('grant_type'),
            'client_id' => $request->get('client_id'),
            'redirect_uri' => $request->get('redirect_uri'),
            'code_verifier' => $request->get('code_verifier'),
            'code' => $request->request->get('code'),
        ]);

        //$response = $next($request);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $access_token = json_decode($response->getContent())->access_token;
            //Log::info("AccessToken: $access_token");
        }
        $tokendata = explode('.', $access_token);
        //Log::info("Tokendata: " . base64_decode($tokendata[1]));
        $jsondata = json_decode(base64_decode($tokendata[1]));
        $user_id = $jsondata->sub;
        $user = User::where("id", $user_id)->first();
        //Log::info("User ID: $user");

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

        $scopesStr = implode(' ', $scopes);
        //$scopesStr = join(" ", $scopes);
        $request->merge(['scopes' => $scopesStr]);
        //Log::info("Scopes: " . $request->get('scopes'));

        return Http::timeout(120)->asForm()->post(url('oauth/token'), [
            'grant_type' => $request->get('grant_type'),
            'client_id' => $request->get('client_id'),
            'redirect_uri' => $request->get('redirect_uri'),
            'code_verifier' => $request->get('code_verifier'),
            'code' => $request->request->get('code'),
            'scopes' => $scopesStr,
        ]);
        /*if ($response->getStatusCode() === Response::HTTP_OK) {
            $access_token = json_decode($response->getContent())->access_token;
            Log::info("AccessToken: $access_token");
        }

        $tokendata = explode('.', $access_token);
        Log::info("Tokendata: " . base64_decode($tokendata[1]));
        $jsondata = json_decode(base64_decode($tokendata[1]));

        $user_id = $jsondata->sub;
        $user = User::where("id", $user_id)->first();
        Log::info("User ID: $user");*/

        //return $next($request);

        //$user = Auth::user();
        /*$requestUser = Request::create('/user', 'GET',[],[],[],[
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        Log::info("Request User: " . $requestUser->header("Authorization"));
        $responseUser = Route::dispatch($requestUser);
        if ($responseUser->getStatusCode() === Response::HTTP_OK) {
            $user = json_decode($response->getContent())->access_token;
            Log::info("User $user");
        }*/


        //return $response;

    }

    /**
     * @throws \Throwable
     */
    public function setupApp(Request $request)
    {
        DB::beginTransaction();

        try {

            $superAdmins = superAdmin::all();
            if($superAdmins->isEmpty()){
                $jsonStr = file_get_contents('init.json');
                $json = json_decode($jsonStr);
                $superAdminid = Str::uuid()->toString();
                $superAdminData = [
                    'id' => $superAdminid,
                    'name' => $json->name,
                    'email' => $json->email,
                    'username' => $json->username,
                    'phone_number' => $json->phone_number,
                    'password' => Hash::make($json->password),
                    'active' => true,
                    'is_super_admin' => true,
                ];
                $superAdmin = SuperAdmin::create($superAdminData);
                SuperAdminFirstTimeConnection::create([
                    'id' => $superAdmin->id,
                    'has_been_connected' => false,
                ]);
                $filename = "./init.json";
                if (file_exists($filename)) {
                    unlink($filename);
                    /*if (!unlink($filename)) {
                        session()->flash('error', __('Une erreur est survenue, reessayez de nouveau.'));
                        return back()->withErrors(['error' => __('Une erreur est survenue, reessayez de nouveau.')]);
                    }*/
                } /*else {
                session()->flash('error', __('Une erreur est survenue, reessayez de nouveau.'));
                return back()->withErrors(['error' => __('Une erreur est survenue, reessayez de nouveau.')]);
            }*/
            }

            $tenant = Tenant::where('id', Tenant::DEFAULT_TENANT_ID)->first();
            if($tenant === null){
                Tenant::create([
                    'id' => Tenant::DEFAULT_TENANT_ID,
                    'name' => "DEFAULT TENANT",
                    'city' => "DOUALA",
                    'location' => "Bonamoussadi",
                    'region' => "Littoral",
                    'country' => "CM",
                    //'logo' => $path,
                    'phone_number' => "+237691179154",
                    'website' => url('/'),
                    'address' => "Douala-Bonamoussadi",
                    'active' => true,
                    'taxpayer_number' => "P088112519572E",
                    'number_trade_register' => "P088112519572E",
                    'network' => json_encode(array()),
                ]);
            }

        }catch (\Exception $e){
            DB::rollBack();
            session()->flash('error', $e->getMessage());
            return redirect()->to('/login')->with('error', $e->getMessage());
        }
        DB::commit();

        return view('welcome');
    }

}
