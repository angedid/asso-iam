@php
@endphp
@extends('layouts.app-super_admin')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            @include('layouts.menu')
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header" style="border-bottom: 0 #D3045D solid;">
                        <h4 style="display: inline; width: 100%; text-align: center; color: #D3045D;">
                            <strong>{{ __("Détails du Tenant") . ': ' . $tenant->name }}</strong>
                        </h4>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="list-group list-group-flush alert alert-{{($tenant->active)?'success':'danger'}}">
                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%; text-align: center;">
                                {{--<h5 style="width: 100%; text-align: center;">--}}
                                    <img src="{{asset('storage/' . $tenant->logo)}}" alt="No logo set"
                                        height="100" width="100">
                                {{--</h5>--}}
                            </a>
                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5 style="display: inline;">
                                    {{__("Nom")}}: &nbsp; &nbsp; {{$tenant->name}}
                                </h5>
                                <h5 style="float: right;">
                                    {{__("Proprietaire")}}  : &nbsp; &nbsp; {{$owner->full_name}}
                                </h5>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5 style="display: inline;">
                                    {{__("Ville")}}: &nbsp; {{$tenant->city}}
                                </h5>
                                <h5 style="float: right;">
                                    {{__("Residence")}}: &nbsp; {{$tenant->location}}
                                </h5>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5 style="display: inline;">
                                    {{__("Region")}}: &nbsp; {{$tenant->region}}
                                </h5>
                                <h5 style="float: right;">
                                    {{__("Pays")}}: &nbsp; {{$country}}
                                </h5>
                            </a>

                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5 style="display: inline;">
                                    {{__("Telephone")}}: &nbsp; {{$tenant->phone_number}}
                                </h5>
                                <h5 style="float: right;">
                                    {{__("Site Web")}}: &nbsp; {{$tenant->website}}
                                </h5>
                            </a>

                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5 style="display: inline;">
                                    {{__("Numero de contribuable")}}: &nbsp; {{$tenant->taxpayer_number}}
                                </h5>
                                <h5 style="float: right;">
                                    {{__("Registre de commerce")}}: &nbsp; {{$tenant->number_trade_register}}
                                </h5>
                            </a>

                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5 style="display: inline;">
                                    {{__("Adresse")}}: &nbsp; {{$tenant->address}}
                                </h5>
                                <h5 style="float: right;">
                                    {{__("Licence")}}: &nbsp; {{ "N/D" }}
                                </h5>
                            </a>

                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5>
                                    {{__("Collaborateur")}}:
                                    <ol>
                                        @foreach($users as  $user)
                                            <li>{{$user->full_name}}</li>
                                        @endforeach
                                    </ol>
                                </h5>
                            </a>

                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5>
                                    {{__("Reseau")}}:
                                    <ol>
                                        @foreach($vendors as  $vendor)
                                            <li>{{$vendor}}</li>
                                        @endforeach
                                    </ol>
                                </h5>
                            </a>

                            <a href="#" class="list-group-item list-group-item-action"
                               style="margin-left: 15px; width: 98%;">
                                <h5>
                                    {{__("Enregistre le")}}: &nbsp; {{ \Illuminate\Support\Carbon::parse($tenant->created_at)->format('d-m-Y H:i:s') }}
                                </h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
