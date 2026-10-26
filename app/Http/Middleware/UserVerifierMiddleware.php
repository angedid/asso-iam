<?php

namespace App\Http\Middleware;

use App\Domain\Model\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UserVerifierMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //Log::info("UserVerifierMiddleware");
        $url = $request->url();
        if ($url === url('/login') && $request->isMethod('POST')) {

            Log::info("LOGIN REQUEST: " . json_encode($request->all()));

            $username = trim($request->get('username'));
            $tenant_id = trim($request->get('tenant_id'));
            $username = $username . "@" . $tenant_id;


            $user = User::where('username', $username)->first();

            $request->offsetUnset('username');
            $request->merge(['username' => $username]);
            Log::info("LOGIN REQUEST NEW: " . json_encode($request->all()));


            if ($user === null) {
                session()->flash('error', __("Pseudo inconnu."));
                return response()->json(['error' => 1, 'success'=>0, 'errorMessage' => __("Pseudo inconnu."), 'successMessage' =>'', 'result' => array()], Response::HTTP_OK);
            }
            if ($user->type === User::MERCHANT_USER){
                $tenant = Tenant::where('id', $user->tenant_id)->where('active', true)->first();
                if ($tenant === null) {
                    session()->flash('error', __("Marchand inconnu."));
                    //Log::info("Marchand inconnu.");
                    return response()->json(['error' => 1, 'success'=>0, 'errorMessage' => __("Marchand inconnu."), 'successMessage' =>'', 'result' => array()], Response::HTTP_OK);

                    //return __("Marchand inconnu.");//redirect('/login')->with('error', __("Marchand inconnu."));
                    //return view('auth.login', ['error'=>__("Marchand inconnu.")]);
                    //return redirect()->route('login')->withErrors(['error'=>__("Marchand inconnu.")]);
                    //return back()->with(['error'=> __("Marchand inconnu.")]);
                    //return back()->with('error', __("Marchand inconnu."));
                }
            }
        }
        return $next($request);
    }
}
