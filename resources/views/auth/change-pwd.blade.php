@php
@endphp
@extends('layouts.app-change-pwd')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            {{--@include('layouts.menu')--}}
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Modification du mot de passe') }}</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('password.reset.first.time.post') }}">
                            <div><h5>{{__('Les champs marqués par ')}} <b class="" style="color: red;">*</b> {{__('sont obligatoires')}}</h5>
                            </div>
                            <br>
                            @csrf
                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
                                </div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert-danger" role="alert">
                                    {{ session('error') }}
                                </div>
                            @endif
                            {{-- @if (isset($status))
                                 <div class="alert alert-info" role="alert">
                                     {{ $status }}
                                 </div>
                             @endif--}}

                            <h5>{{ __('Bien vouloir changer votre mot de passe') }}</h5>
                            <br>

                            <div class="row mb-3">
                                <label for="password" class="col-md-4 col-form-label text-md-end">
                                    {{ __('Mot de passe') }}
                                    <b class="" style="color: red;">*</b>
                                </label>

                                <div class="col-md-6">
                                    <input id="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror" name="password"
                                           required autocomplete="new-password">

                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password-confirm" class="col-md-4 col-form-label text-md-end">
                                    {{ __('Confirmer le mot de passe') }}
                                    <b class="" style="color: red;">*</b>
                                </label>

                                <div class="col-md-6">
                                    <input id="password-confirm" type="password" class="form-control"
                                           name="password_confirmation" required autocomplete="new-password">
                                </div>
                            </div>

                            <input type="hidden" name="userid" value="{{$user->id}}">


                            <div class="row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Changer') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
