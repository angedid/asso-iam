
@extends('layouts.email-template')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>{{ __("Demande d'initialisation de mot de passe sur la plateforme ") . $data['tenant_name'] }} <br></h3>
                        {{ __('Cher/Chere') . ' ' . $data['name'] }}
                    </div>
                    <div class="card-body">
                        <p>
                            Vous avez demande la reinitialisation de votre mot de passe sur la plateforme
                              <strong>{{$data['tenant_name']}} </strong>.
                            <br>
                        </p>
                        <p>
                            Veuillez trouver ci-dessous le lien de reinitialisation.
                        </p>
                        <p>
                            <a href="{{$data['url']}}">{{__("Cliquez sur ce lien pour reinitialiser votre mot de passe")}}</a>
                            <br>
                        </p>
                        <h5>{{ __("Merci pour votre collaboration.") }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


