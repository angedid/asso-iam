
@extends('layouts.email-template')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>{{ 'Invitation a s\'enregistrer  a la plateforme de ' . $data['tenant_name'] }} <br></h3>
                    {{ __('Cher/Chere') . ' ' . $data['name'] }}
                    </div>

                    <div class="card-body">
                        <p>
                            Vous avez ete invite par la plateforme de  <strong>{{$data['tenant_name']}} </strong>.
                            a vous enregistrer.
                            <br>

                        </p>
                        <p>
                            {{ __("Vous pouvez accéder au système en utilisant le lien suivant") }}: &nbsp; <a href="{{$data['url']}}">{{$data['url']}}</a>
                            <br>
                        </p>

                            <h5>{{ __("Merci pour votre collaboration.") }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


