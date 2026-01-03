@extends('layouts.app-super_admin')

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

                        <div  class="">
                            <div class="card-body" style="border: 1px #D3045D solid; border-radius: 10px;">
                                <br>
                                <form method="POST" action="{{ route('auth.super-admins.login.post') }}">
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

                                    <div class="row mb-0">
                                        <div class="col-md-8 offset-md-4">
                                            <button type="submit" class="btn btn-primary"
                                                    style="background: #D3045D; border: 1px #D3045D solid;">
                                                <strong style="color: white;">{{ __('Connexion') }}</strong>
                                            </button>

                                            @if (Route::has('password.request'))
                                                <a class="btn btn-link" href="{{ route('password.request') }}" style="color:#D3045D;">
                                                    <strong>{{ __('Forgot Your Password?') }}</strong>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <br>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
