<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Model\SuperAdmin;
use App\Domain\Model\Tenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HomeSuperAdminController extends Controller
{

    public function __construct()
    {
        $this->middleware('super-admins-auth');
    }

    public function superAdminDashboard()
    {
        $superAdmins = SuperAdmin::where('id', '!=', Auth::guard('super_admin')->user()->id)->get();
        return view('super-admin.list', compact('superAdmins'));
    }

    public function logout()
    {
        Session::flush();
        Auth::guard('super_admin')->logout();
        return redirect()->route('auth.super-admins.login');
    }

    public function superAdminList()
    {
        $superAdmins = SuperAdmin::where('id', '!=', Auth::guard('super_admin')->user()->id)->get();
        return view('super-admin.list', compact('superAdmins'));
    }

    public function showRegisterSuperAdminForm()
    {
        return view('super-admin.register');
    }

    public function removeOrAddToAdminRole(Request $request, string $userid)
    {
        $validator = Validator::make($request->all(), [
            'operation' => 'required|string|in:remove,add',
        ]);
        if($validator->fails()){
            session()->flash('error', $validator->errors()->first());
            return back()->withErrors(['error' => $validator->errors()->first()]);
        }
        $user =SuperAdmin::where('id', $userid)->first();
        if ($user == null) {
            $msg = __("Utilisateur non reconnu!");
            session()->flash('error');
            return back()->withErrors(['error' => $msg]);
        }
        $msg = '';
        if (trim($request->get('operation')) == 'remove') {
            if ($user->username === 'nkalla'){
                $msg = __("Utilisateur enregistré avec succès !");
                session()->flash('success', $msg);
                return back()->with('success', $msg);
            }
            $user->is_super_admin = false;
            $user->save();
            $msg = __("Utilisateur enregistré avec succès !");
        }else{
            //$users = SuperAdmin::where('is_admin', true)->get();
            /*if (count($users) >= 3) {
                session()->flash('error', __("Désole vous ne pouvez plus enregistrer d'administrateur."));
                return back()->withErrors(['error' => __("Désole vous ne pouvez plus enregistrer d'administrateur.")]);
            }*/
            $user->is_super_admin = true;
            $user->save();
            $msg = __("Utilisateur enregistré avec succès !");
        }
        session()->flash('success', $msg);
        return back()->with('success', $msg);
    }

    public function activateUser(Request $request, string $userid): RedirectResponse
    {

        $user =SuperAdmin::where('id', $userid)->first();
        if ($user == null) {
            $msg = __("Utilisateur non reconnu!");
            session()->flash('error');
            return back()->withErrors(['error' => $msg]);
        }

        $user->active = true;
        $user->save();
        $msg = __("Utilisateur débloqué avec succès !");

        session()->flash('success', $msg);
        return back()->with('success', $msg);
    }

    public function deActivateUser(Request $request, string $userid): RedirectResponse
    {
        $user =SuperAdmin::where('id', $userid)->first();
        if ($user == null) {
            $msg = __("Utilisateur non reconnu!");
            session()->flash('error');
            return back()->withErrors(['error' => $msg]);
        }

        if ($user->username === 'nkalla'){
            $msg = __("Utilisateur bloqué avec succès !");

            session()->flash('success', $msg);
            return back()->with('success', $msg);
        }

        $user->active = false;
        $user->save();
        $msg = __("Utilisateur bloqué avec succès !");

        session()->flash('success', $msg);
        return back()->with('success', $msg);
    }

    public function tenantsList()
    {
        $tenants = Tenant::all();
        return view('tenant.list', compact('tenants'));
    }

    public function activateTenant(Request $request, string $tenantid)
    {
        $tenant =Tenant::where('id', $tenantid)->first();
        if ($tenant == null) {
            $msg = __("Tenant non reconnu!");
            session()->flash('error', $msg);
            return back()->with('error', $msg);
        }
        $tenant->active = true;
        $tenant->save();
        $msg = __("Tenant active avec success!");
        session()->flash('success', $msg);
        return back()->with('success', $msg);
    }

    public function deactivateTenant(Request $request, string $tenantid)
    {
        //dd($tenantid);
        $tenant = Tenant::where('id', $tenantid)->first();
        if ($tenant == null) {
            $msg = __("Tenant non reconnu!");
            session()->flash('error', $msg);
            return back()->with('error', $msg);
        }
        $tenant->active = false;
        $tenant->save();
        $msg = __("Tenant desactive avec success!");
        session()->flash('success', $msg);
        return back()->with('success', $msg);
    }

    public function tenantDetail(Request $request, string $tenantid)
    {
        $tenant = Tenant::where('id', $tenantid)->first();
        if ($tenant == null) {
            $msg = __("Tenant non reconnu!");
            session()->flash('error', $msg);
            return back()->with('error', $msg);
        }

        $owner = User::where('id', $tenant->owner)->first();
        if ($owner == null) {
            $msg = __("Tenant sans proprietaire!");
            session()->flash('error', $msg);
            return back()->with('error', $msg);
        }
        $pays = null;
        if ($tenant->country !== null){
            $pays = country($tenant->country);
        }

        $vendorsids = json_decode($tenant->network);
        $vendors = [];
        foreach ($vendorsids as $vendorsid){
            $auser = User::where('id', $vendorsid)->first();
            if ($auser !== null) {
                $vendors[] = $auser->full_name . ' (' . $auser->phone_number . ')';
            }
        }
        return view('tenant.detail', ['tenant' => $tenant, 'owner' => $owner, 'users' => User::where('tenant_id', $tenant->id)->get(),
            'country' => ($tenant->country !== null) ? $pays->getName() . '(' . $tenant->country . ')' : null, 'vendors' => $vendors,]);

    }

}
