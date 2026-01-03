@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card" style="border: 0 #D3045D solid;">
                        <div class="" style="text-align: center; margin-top: 15px; color: #D3045D;">
                            <h3>{{ __('Connexion') }}</h3>
                        </div>
                        <br>
                        <div id="Merchand" class="">
                            <div class="card-body" style="border: 1px #D3045D solid; border-radius: 10px;">
                                <br>
                                <form method="POST" action="{{ route('login') }}">
                                    @if (session('success'))
                                        <div class="alert alert-success" role="alert">
                                            <span>{{ session('success') }}</span>
                                        </div>
                                    @endif
                                    @if (session('error'))
                                        <div class="alert alert-danger" role="alert">
                                            <span>{{ session('error') }}</span>
                                        </div>
                                    @endif

                                    @csrf
                                        {{--<input type="hidden" name="guard" value="seller">--}}
                                    {{--<h6 style="color: red;">Tous les champs sont obligatoires</h6>
                                    <h4 style="color: #164fa9;">Entreprise</h4>--}}

                                        <?php
                                        $tenants = \App\Domain\Model\Tenant::where('id', '!=', \App\Domain\Model\Tenant::DEFAULT_TENANT_ID)->get();
                                        $defaultTenant = \App\Domain\Model\Tenant::where('id', \App\Domain\Model\Tenant::DEFAULT_TENANT_ID)->first();
                                        ?>

                                        <input id="tenant_id" type="hidden"  name="tenant_id" value="">
                                        <input id="default_tenant_id" type="hidden"  name="default_tenant_id" value="{{$defaultTenant->id}}">

                                        <div class="row mb-3">
                                            <label for="activity_type" class="col-md-4 col-form-label text-md-end">{{ __('Activité') }}</label>

                                            <div class="col-md-6" id="activity_type">
                                                <input type="radio" id="merchant" name="activity_type" value="merchant" onclick="toggleTenantFormcontrol(this);">
                                                &nbsp; <label for="merchant">Grossiste</label> &nbsp; &nbsp;&nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;
                                                <input type="radio" id="seller" name="activity_type" value="seller"
                                                       onclick="toggleTenantFormcontrol(document.getElementById('merchant')); setTenantId(document.getElementById('default_tenant_id').value);">
                                                &nbsp; <label for="seller">Sauveteur</label>
                                            </div>
                                        </div>


                                        <div class="row mb-3 form-group" id="tenants" style="display: none;">
                                            <label for="tenant" class="col-md-4 col-form-label text-md-end">{{ __('Entreprise') }}</label>

                                            <div class="col-md-6" >
                                                <select id="tenant" class="form-control form-inline @error('tenant') is-invalid @enderror"  name="tenant"
                                                        onclick="setTenantId(this.value);">
                                                    @foreach($tenants as $tenant)
                                                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('tenant')
                                                <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Pseudo') }}</label>

                                        <div class="col-md-6">
                                            <input id="username" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autocomplete="username" autofocus>

                                            @error('username')
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Mot de passe') }}</label>

                                        <div class="col-md-6">
                                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                            @error('password')
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6 offset-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember"
                                                       id="remember" {{ old('remember') ? 'checked' : '' }}
                                                    style="font-size: large; width: 20px; height: 20px; margin-top: 0; border: 1px #D3045D solid; background-color: #D3045D;">

                                                <label class="form-check-label" for="remember">
                                                    {{ __('Remember Me') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-0">
                                        <div class="col-md-8 offset-md-4">
                                            <button type="submit" class="btn btn-primary" style="background: #D3045D; border: 1px #D3045D solid;">
                                                <strong style="color: white;">{{ __('Connexion') }}</strong>
                                            </button>

                                            @if (Route::has('password.request'))
                                                <a class="btn btn-link" href="{{ route('password.request') }}"
                                                style="color:#D3045D;">
                                                    {{ __('Forgot Your Password?') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>

                                        <script type="text/javascript">
                                            function setTenantId(tntid){
                                                console.log("Seting tenant id: " + tntid);
                                                document.getElementById('tenant_id').setAttribute('value', tntid);
                                            }

                                            function toggleTenantFormcontrol(input){
                                                let tenantselem = document.getElementById('tenants');
                                                //console.log(input.checked?"CHECKED":"UNCHECKED");
                                                if(input.checked){
                                                    if(tenantselem.style.display === 'none'){
                                                        tenantselem.setAttribute('style', 'display: block');
                                                        setTenantId(document.getElementById('tenant').value);
                                                    }
                                                }else{
                                                    if(tenantselem.style.display === 'block'){
                                                        tenantselem.setAttribute('style', 'display: none');
                                                    }
                                                }
                                            }
                                        </script>
                                </form>
                            </div>
                        </div>

                        {{--<div id="Sauveteur" class="tabcontent">
                            <div class="card-body" style="border: 1px #164fa9 solid; border-radius: 10px;">
                                <br>
                                <form method="POST" action="{{ route('seller-login') }}">
                                    @if (session('success'))
                                        <div class="alert alert-success" role="alert">
                                            <h4>{{ session('success') }}</h4>
                                        </div>
                                    @endif
                                    @if (session('error'))
                                        <div class="alert alert-danger" role="alert">
                                            <h4>{{ session('error') }}</h4>
                                        </div>
                                    @endif

                                    @csrf
                                        <input type="hidden" name="guard" value="seller">
                                    --}}{{--<h6 style="color: red;">Tous les champs sont obligatoires</h6>
                                    <h4 style="color: #164fa9;">Entreprise</h4>--}}{{--
                                    <span> 2 </span>
                                    <div class="row mb-3">
                                        <label for="username1" class="col-md-4 col-form-label text-md-end">{{ __('Pseudo') }}</label>

                                        <div class="col-md-6">
                                            <input id="username1" type="text" class="form-control @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required autocomplete="username" autofocus>

                                            @error('username')
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="password1" class="col-md-4 col-form-label text-md-end">{{ __('Mot de passe') }}</label>

                                        <div class="col-md-6">
                                            <input id="password1" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                            @error('password')
                                            <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6 offset-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remember" id="remember1" {{ old('remember') ? 'checked' : '' }}>

                                                <label class="form-check-label" for="remember1">
                                                    {{ __('Remember Me') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-0">
                                        <div class="col-md-8 offset-md-4">
                                            <button type="submit" class="btn btn-primary" style="background: #164fa9; border: 1px #164fa9 solid;">
                                                {{ __('Connexion') }}
                                            </button>

                                            @if (Route::has('password.request'))
                                                <a class="btn btn-link" href="{{ route('password.request') }}">
                                                    {{ __('Forgot Your Password?') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </form>

                                <script type="text/javascript">
                                    function openFirstTab(){
                                        var tab1btn = document.getElementById('merchand_connection');
                                        tab1btn.click();
                                    }
                                    openFirstTab();
                                </script>
                            </div>
                        </div>--}}

                        {{--<div id="Tokyo" class="tabcontent">
                            <h3>Tokyo</h3>
                            <p>Tokyo is the capital of Japan.</p>
                        </div>--}}





                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection








