@extends('layouts.app-super_admin')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            @include('layouts.menu')
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header" style="
                                text-align: left;
                                color: #D3045D;
                                font-weight: bold;
                                display: inline;
                                padding-top: 10px;
                                margin-bottom: 0;
                                border-bottom: 0 #D3045D solid;
                                ">
                        <h4 style="display: inline;">
                            {{ __('Enregistrement Super Administrateur') }}
                        </h4>
                        <h5 style="display: inline; float: right;">
                            {{--@if(count(Config::where('is_applicable', true)->get()) > 0)--}}
                            <a href="{{ route('auth.super-admins.register')}}"
                               style="text-decoration: none; font-size: x-large; color: #D3045D;"
                               id="add_level_field"
                               title="{{__('Enregistrer un super Administrateur')}}">
                                <strong><span class="glyphicon glyphicon-plus">+</span></strong>
                                <span style="font-size: initial;">{{ __('Ajouter') }}</span>
                            </a>
                            {{--@endif--}}
                        </h5>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif
                            @if (session('error'))
                                <div class="alert alert-danger" role="alert">
                                    {{ session('error') }}
                                </div>
                            @endif
                        @if(count($superAdmins) > 0)
                                <table class="table table-striped table-responsive table-bordered">
                                    <thead class="" style="color: #D3045D;">
                                        <th scope="col">
                                            {{ __('#') }}
                                        </th>

                                        <th scope="col">
                                            {{ __('Nom') }}
                                        </th>

                                        <th scope="col">
                                            {{ __('Email') }}
                                        </th>
                                        <th scope="col">
                                            {{ __('Pseudo') }}
                                        </th>
                                        <th scope="col">
                                            {{ __('Actions') }}
                                        </th>
                                    </thead>
                                    <tbody>
                                    @php $rank = 1; @endphp
                                    @foreach($superAdmins as $user)
                                        <tr>
                                            <th scope="row">
                                                {{ $rank }}
                                            </th>

                                            <td >
                                                {{$user->name}}
                                            </td>

                                            <td >
                                                {{$user->email}}
                                            </td>
                                            <td >
                                                {{$user->username}}
                                            </td>
                                            <td>
                                                @if($user->is_super_admin)
                                                    <a class="btn btn-sm btn-link" href="#" data-bs-toggle="modal"
                                                       data-bs-target="#confirm-remove-admin-role-modal{{$user->id}}" style="text-decoration: none;">
                                                        <b style="color: red;">{{ __('Retrait Rôle') }}</b>
                                                    </a>
                                                    <div class="modal fade" id="confirm-remove-admin-role-modal{{$user->id}}"
                                                         data-bs-backdrop="static"
                                                         data-bs-keyboard="false" tabindex="-1"
                                                         aria-labelledby="staticBackdropLabel"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                        {{__("Retrait du rôle Super Administrateur de l'utilisateur:")}} {{$user->name}}
                                                                    </h1>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST"
                                                                      action="{{ route('super.admin.add.or.remove.role', $user->id) }}"
                                                                      onsubmit="return true;">
                                                                    <div class="modal-body">

                                                                        <input type="hidden" name="error" id="error"
                                                                               class="form-control @error('error') is-invalid @enderror">
                                                                        @error('error')
                                                                        <span class="invalid-feedback" role="alert"
                                                                              style="position: relative; width: 100%; text-align: center;">
                                                                    <strong>{{ $message }}</strong>
                                                                </span> <br/>
                                                                        @enderror

                                                                        @csrf
                                                                        <input type="hidden" name="operation" value="remove">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger"
                                                                                data-bs-dismiss="modal">Annuler
                                                                        </button>
                                                                        <button type="submit" class="btn btn-success">
                                                                            {{__("Confirmer")}}
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else

                                                    <a class="btn btn-sm btn-link" href="#" data-bs-toggle="modal"
                                                       data-bs-target="#confirm-add-admin-role-modal{{$user->id}}" style="text-decoration: none;">
                                                        <b style="color: limegreen;">{{ __('Ajout Rôle') }}</b>
                                                    </a>
                                                    <div class="modal fade" id="confirm-add-admin-role-modal{{$user->id}}"
                                                         data-bs-backdrop="static"
                                                         data-bs-keyboard="false" tabindex="-1"
                                                         aria-labelledby="staticBackdropLabel"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                        {{__("Ajout du rôle Super Administrateur à l'utilisateur:")}} {{$user->name}}
                                                                    </h1>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST"
                                                                      action="{{ route('super.admin.add.or.remove.role', $user->id) }}"
                                                                      onsubmit="return true;">
                                                                    <div class="modal-body">

                                                                        <input type="hidden" name="error" id="error"
                                                                               class="form-control @error('error') is-invalid @enderror">
                                                                        @error('error')
                                                                        <span class="invalid-feedback" role="alert"
                                                                              style="position: relative; width: 100%; text-align: center;">
                                                                    <strong>{{ $message }}</strong>
                                                                </span> <br/>
                                                                        @enderror

                                                                        @csrf
                                                                        <input type="hidden" name="operation" value="add">

                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger"
                                                                                data-bs-dismiss="modal">Annuler
                                                                        </button>
                                                                        <button type="submit" class="btn btn-success">
                                                                            {{ __("Confirmer") }}
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                @endif

                                                @if($user->active)
                                                    <a class="btn btn-sm btn-link" href="#" data-bs-toggle="modal"
                                                       data-bs-target="#confirm-block-user-modal{{$user->id}}" style="text-decoration: none;">
                                                        <b style="color: red;">{{ __('Bloquer') }}</b>
                                                    </a>
                                                    <div class="modal fade" id="confirm-block-user-modal{{$user->id}}"
                                                         data-bs-backdrop="static"
                                                         data-bs-keyboard="false" tabindex="-1"
                                                         aria-labelledby="staticBackdropLabel"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                        {{__("Bloquer l'utilisateur")}}: {{$user->name}}
                                                                    </h1>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST"
                                                                      action="{{ route('super.admin.deactivate', $user->id) }}"
                                                                      onsubmit="return true;">
                                                                    <div class="modal-body">

                                                                        <input type="hidden" name="error" id="error"
                                                                               class="form-control @error('error') is-invalid @enderror">
                                                                        @error('error')
                                                                        <span class="invalid-feedback" role="alert"
                                                                              style="position: relative; width: 100%; text-align: center;">
                                                                    <strong>{{ $message }}</strong>
                                                                </span> <br/>
                                                                        @enderror

                                                                        @csrf
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger"
                                                                                data-bs-dismiss="modal">Annuler
                                                                        </button>
                                                                        <button type="submit" class="btn btn-success">
                                                                            {{__("Confirmer")}}
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else

                                                    <a class="btn btn-sm btn-link" href="#" data-bs-toggle="modal"
                                                       data-bs-target="#confirm-block-user-modal{{$user->id}}" style="text-decoration: none;">
                                                        <b style="color: limegreen;">{{ __("Débloquer") }}</b>
                                                    </a>
                                                    <div class="modal fade" id="confirm-block-user-modal{{$user->id}}"
                                                         data-bs-backdrop="static"
                                                         data-bs-keyboard="false" tabindex="-1"
                                                         aria-labelledby="staticBackdropLabel"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                        {{__("Débloquer l'utilisateur")}}: {{$user->name}}
                                                                    </h1>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST"
                                                                      action="{{route('super.admin.activate', $user->id)}}"
                                                                      onsubmit="return true;">
                                                                    <div class="modal-body">

                                                                        <input type="hidden" name="error" id="error"
                                                                               class="form-control @error('error') is-invalid @enderror">
                                                                        @error('error')
                                                                        <span class="invalid-feedback" role="alert"
                                                                              style="position: relative; width: 100%; text-align: center;">
                                                                    <strong>{{ $message }}</strong>
                                                                </span> <br/>
                                                                        @enderror

                                                                        @csrf
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-danger"
                                                                                data-bs-dismiss="modal">Annuler
                                                                        </button>
                                                                        <button type="submit" class="btn btn-success">
                                                                            {{__("Confirmer")}}
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                @endif

                                            </td>
                                        </tr>
                                        @php $rank = $rank + 1; @endphp
                                    @endforeach
                                    </tbody>
                                </table>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
