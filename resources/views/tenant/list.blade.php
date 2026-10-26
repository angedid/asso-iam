@php use App\Models\User; @endphp
@extends('layouts.app-super_admin')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            @include('layouts.menu')
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header" style="
                                text-align: center;
                                color: #D3045D;
                                font-weight: bold;
                                display: inline;
                                width: 100%;
                                padding-top: 10px;
                                margin-bottom: 0;
                                border-bottom: 0 #D3045D solid;
                                ">
                        <h4 style="display: inline;">
                            {{ __('Tenanciers') }}
                        </h4>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif
                        @if(count($tenants) > 0)
                                <table class="table table-striped table-responsive table-bordered">
                                    <thead class="" style="color: #D3045D;">
                                        <th scope="col">
                                            {{ __('#') }}
                                        </th>

                                        <th scope="col">
                                            {{ __('Nom') }}
                                        </th>

                                        <th scope="col">
                                            {{ __('Proprietaire') }}
                                        </th>
                                        <th scope="col">
                                            {{ __('Ville') }}
                                        </th>
                                        <th scope="col">
                                            {{ __('Actions') }}
                                        </th>
                                        <th scope="col">
                                            {{ __('Plus...') }}
                                        </th>
                                    </thead>
                                    <tbody>
                                    @php $rank = 1; @endphp
                                    @foreach($tenants as $tenant)
                                        <tr>
                                            <th scope="row">
                                                {{ $rank }}
                                            </th>

                                            <td >
                                                {{$tenant->name}}
                                            </td>

                                            <td >
                                                <?php $owner = User::where('id', $tenant->owner)->first(); ?>
                                                {{$owner->full_name}}
                                            </td>
                                            <td >
                                                {{$tenant->city}}
                                            </td>
                                            <td>

                                                @if($tenant->active)
                                                    <a class="btn btn-sm btn-link" href="#" data-bs-toggle="modal"
                                                       data-bs-target="#confirm-block-tenant-modal{{$tenant->id}}" style="text-decoration: none;">
                                                        <b style="color: red;">{{ __('Bloquer') }}</b>
                                                    </a>
                                                    <div class="modal fade" id="confirm-block-tenant-modal{{$tenant->id}}"
                                                         data-bs-backdrop="static"
                                                         data-bs-keyboard="false" tabindex="-1"
                                                         aria-labelledby="staticBackdropLabel"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                        {{__("Bloquer le tenancier")}}: {{$tenant->name}}
                                                                    </h1>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST"
                                                                      action="{{ route('super-admin.tenant.deactivate', $tenant->id) }}"
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
                                                       data-bs-target="#confirm-block-tenant-modal{{$tenant->id}}" style="text-decoration: none;">
                                                        <b style="color: limegreen;">{{ __("Débloquer") }}</b>
                                                    </a>
                                                    <div class="modal fade" id="confirm-block-tenant-modal{{$tenant->id}}"
                                                         data-bs-backdrop="static"
                                                         data-bs-keyboard="false" tabindex="-1"
                                                         aria-labelledby="staticBackdropLabel"
                                                         aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                        {{__("Débloquer le tenancier")}}: {{$tenant->name}}
                                                                    </h1>
                                                                    <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST"
                                                                      action="{{route('super-admin.tenant.activate', $tenant->id)}}"
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
                                            <td >
                                                <a href="{{route('tenant.details', ['tenantid' => $tenant->id])}}"
                                                   class="list-group-item list-group-item-action">
                                                    <img src="{{asset('images/icons8-right-chevron-25.png')}}" alt=">"/>
                                                </a>
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
