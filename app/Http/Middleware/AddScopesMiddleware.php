<?php

namespace App\Http\Middleware;

use App\Domain\Model\OauthAuthCode;
use App\Domain\Model\Role;
use App\Domain\Model\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class AddScopesMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $url = $request->url();
        if ($url === url('/oauth/token') && $request->isMethod('POST')) {
            //Log::info("Request: " . json_encode($request->all()));
            $code = $request->request->get('code');
            //$code = OauthAuthCode::where('id', $code_id)
            //Log::info("Code: $code");
            $response = $next($request);
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

            return $next($request);

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

        return $next($request);
    }
}
