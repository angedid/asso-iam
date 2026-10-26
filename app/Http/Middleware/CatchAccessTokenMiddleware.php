<?php

namespace App\Http\Middleware;

use App\Domain\Model\Role;
use App\Jobs\ProcessMessages;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CatchAccessTokenMiddleware
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
            $response = $next($request);
            if ($response->getStatusCode() === Response::HTTP_OK) {
                //$access_token = json_decode($response->getContent())->access_token;
                $access_token = $response->getContent();
                ProcessMessages::dispatch(env('USER_AUTHENTICATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($access_token));

                /*Log::info("AccessToken: $access_token");
                $tokendata = explode('.', $access_token);
                Log::info("Tokendata: " . base64_decode($tokendata[1]));
                $jsondata = json_decode(base64_decode($tokendata[1]), true);
                $user_id = $jsondata->sub;*/
            }
            return $response;
            //return $next($request);
        }
        return $next($request);
    }
}
