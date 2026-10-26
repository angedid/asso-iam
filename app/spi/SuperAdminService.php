<?php

namespace App\spi;

use App\Domain\DomainServices\DomainServices;
use App\Domain\Model\SuperAdmin;
use App\Domain\Model\SuperAdminFirstTimeConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class SuperAdminService
{
    protected DomainServices $domainServices;
    public function __construct(DomainServices $domainServices)
    {
        $this->domainServices = $domainServices;
    }

    public function registerSuperAdmin(Request $request)
    {
        $validatorSuperAdmin = DomainServices::makeSuperAdminValidator($request);

        if ($validatorSuperAdmin->fails()) {
            session()->flash('error', $validatorSuperAdmin->errors()->first());
            return back()->withErrors(['error' => $validatorSuperAdmin->errors()->first()]);
        }

        DB::beginTransaction();
        try {
            $superAdminid = Str::uuid()->toString();

            $superAdminData = [
                'id' => $superAdminid,
                'name' => trim($request->get('name')),
                'email' => trim($request->get('email')),
                'username' => trim($request->get('username')),
                'phone_number' => trim($request->get('phone_number')),
                'password' => Hash::make($request->get('password')),
                'active' => true,
                'is_super_admin' => true,
            ];

            $uperAdmin = SuperAdmin::create($superAdminData);
            SuperAdminFirstTimeConnection::create([
                'id' => $uperAdmin->id,
                'has_been_connected' => false,
            ]);

        }catch (\Exception $exception){
            DB::rollBack();
            session()->flash('error', $exception->getMessage());
            return back()->withErrors(['error' =>  $exception->getMessage()]);
        }

        DB::commit();
        $msg = __("Super Administrateur enregistré avec succès !");
        session()->flash('success', $msg);
        return back()->with('success', $msg);//->withSuccess('status', 'Great! You have Successfully Registered.');
    }
    public function postLoginSuperAdmin(Request $request)
    {
        $validatorSuperAdminCredentials = DomainServices::makeSuperAdminCredentialsValidator($request);

        if ($validatorSuperAdminCredentials->fails()) {
            session()->flash('error', $validatorSuperAdminCredentials->errors()->first());
            return back()->withErrors(['error' => $validatorSuperAdminCredentials->errors()->first()]);
        }

        $superAdmin = SuperAdmin::where('username', $request->get('username'))->first();
        $superAdminFirstimeConnection = SuperAdminFirstTimeConnection::where('id', $superAdmin->id)->first();

        if (!$superAdmin->active) {
            session()->flash('error', 'Votre compte est inactif.');
            return back()->withErrors([
                'error' => 'Votre compte est inactif.',
            ]);
        }
        $credentials = $request->only('username', 'password');
        $credentials['active']=true;
        if (Auth::guard('super_admin')->attempt($credentials)) {
            if (!$superAdminFirstimeConnection->has_been_connected){
                Auth::guard('super_admin')->logout();
                session()->flash('success', __('Bien vouloir choisir un nouveau mot de passe'));
                return view('auth.change-pwd',
                    ['user'=>$superAdmin, 'success' => __('Bien vouloir choisir un nouveau mot de passe')]);
            }
            $request->session()->regenerate();
            session()->flash('success', __("Connexion réussie !"));
            session()->flash('success', __("Connexion réussie !"));
            return redirect()->route('super-admin-dashboard')->withSuccess('success', __("Connexion réussie !"));
        }

        session()->flash('error', __("Nom d'utilisateur ou mot de passe incorrect"));
        return back()->withErrors([
            'error' => __("Nom d'utilisateur ou mot de passe incorrect"),
        ]);
    }

    public function postResetPasswordFirstConnection(Request $request)
    {
        if ($request->filled('password')) {
            if ($request->get('password') === 'Asso123!!'){
                $msg = __("Le mot de passe doit être obligatoirement modifié.");
                session()->flash('error', $msg);
                return back()->withErrors(['error' => $msg]);
            }
        }
        $validator = DomainServices::makeChangePasswordFirstConnectionValidator($request);

        if($validator->fails()){
            session()->flash('error', $validator->errors()->first());
            return back()->withErrors(['error' => $validator->errors()->first()]);
        }

        $user = SuperAdmin::where('id', $request->get('userid'))->first();
        $user->update(['password' => Hash::make($request->get('password'))]);
        Session::flush();
        Auth::guard('super_admin')->logout();
        $msg = __('Modification reussie !');
        session()->flash('status', $msg);

        $request->merge(['username' => $user->username]);

        $credentials = $request->only('username', 'password');
        $credentials['active']=true;
        if (Auth::guard('super_admin')->attempt($credentials)){
            $request->session()->regenerate();
            $msg = __('Modification reussie !');
            session()->flash('status', $msg);
            $userFirstTimeConnection = SuperAdminFirstTimeConnection::where('id', $user->id)->first();
            $userFirstTimeConnection->update(['has_been_connected' => true]);
            $userFirstTimeConnection->has_been_connected = true;
            $userFirstTimeConnection->save();
            return redirect()->route('super-admin-dashboard')->withSuccess('success', 'Vous etes connecte avec succes!.');
        }
        return back()->withErrors([
            'error' => __('Nom Utilisateur et/ou Mot de passe incorrects !'),
        ]);
    }

}
