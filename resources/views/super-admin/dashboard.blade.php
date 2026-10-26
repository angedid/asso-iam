@extends('layouts.app-super_admin')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            @include('layouts.menu')
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header" style="
                                text-align: center;
                                width: 100%;
                                font-size: 3em;
                                color: #D3045D;
                                font-weight: bold;
                                margin-bottom: 0;
                                border-bottom: 0 #D3045D solid;
                                ">
                        <h4 >
                            {{ __('Dashboard') }}
                        </h4>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{ __('You are logged in!') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
