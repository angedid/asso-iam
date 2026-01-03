<div class="col-md-3">
    <div class="card" style="border: 1px #D3045D solid;">
        <div class="" style="
                                text-align: center;
                                width: 100%;
                                font-size: 3em;
                                color: #D3045D;
                                font-weight: bold;
                                margin-top: 15px;
                                margin-bottom: 0;
                                border-bottom: 1px #D3045D solid;
                                ">
            <h5 >
                {{ __('Menu principal') }}
            </h5>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action btn btn-link"
                   href="{{ route('auth.super-admins.list') }}">
                    <h6>{{ __('Administrateurs') }}</h6>
                </a>
                <a class="list-group-item list-group-item-action btn btn-link"
                   href="{{ route('super-admin.tenants') }}">
                    <h6>{{ __('Tenanciers') }}</h6>
                </a>
            </div>
        </div>
    </div>
</div>


