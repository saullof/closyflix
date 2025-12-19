@extends('voyager::master')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-dashboard"></i> {{__("Platform statistics")}}
    </h1>
@stop

@section('content')
    <div class="page-content">
        @include('voyager::alerts')
        @include('voyager::dimmers')
        <div class="analytics-container">

        <div class="analytics-container">

            <!-- Card de alerta com verificações e saques -->
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info d-flex justify-content-between align-items-center" style="background-color: #D54651; color: #fff; border-radius: 8px; padding: 1rem 1.5rem;">
                        <div>
                            <strong>⚠️ Há novos eventos que exigem sua atenção.</strong>  
                            <p style="margin: 0; font-size: 0.9rem;">Por favor, revise-os em:</p>
                            <ul style="list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem;">
                                <li>
                                    Verificações de ID do usuário (<strong>{{ $totalPerfisPendentes }}</strong>)
                                </li>
                                <li>
                                    Retiradas (<strong>{{ $totalSaques }}</strong>)
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>


            @if(!checkMysqlndForPDO() || !checkForMysqlND())
                <div class="storage-incorrect-bucket-config tab-additional-info">
                    <div class="alert alert-warning alert-dismissible mb-1">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="info-label"><div class="icon voyager-info-circled"></div><strong>{{__("Warning!")}}</strong></div>
                        <div class=""> {{__("Your PHP's pdo_mysql extension is not using mysqlnd driver. ")}} {{__('This might cause different UI related issues.')}}
                            <div class="mt-05">{{__("Please contact your hosting provider and check if they can enable mysqlnd for pdo_mysql as default driver. Alternatively, you can check if the other PHP versions act the same. ")}}</div>
                        <div class="mt-05">
                            <ul>
                                <li>{{__("Mysqlnd loaded:")}} <strong>{{checkForMysqlND() ? __('True') : __('False')}}</strong></li>
                                <li>{{__("Mysqlnd for PDO:")}} <strong>{{checkMysqlndForPDO()  ? __('True') : __('False')}}</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @include('elements.admin.metrics')

            <div class="row">
                <div class="mb-4 col-md-4">
                    <div class="card shadow rounded p-5">
                        <div class="card-body text-muted font-weight-medium">
                            <a href="https://codecanyon.net/item/justfans-premium-content-creators-saas-platform/35154898" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center info-category-bg">
                                        <div class="icon voyager-world info-category-icon"></div>
                                    </div>
                                    <div class="ml-4 d-flex align-items-center">
                                        <div>
                                            <div class="text-muted font-weight-bolder">{{__("Website")}}</div>
                                            <p class="m-0 text-muted">{{__("Visit the official product page")}}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mb-4 col-md-4">
                    <div class="card shadow rounded p-5">
                        <div class="card-body text-muted font-weight-medium">
                            <a href="https://docs.qdev.tech/justfans/" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center info-category-bg">
                                        <div class="icon voyager-book info-category-icon"></div>
                                    </div>
                                    <div class="ml-4 d-flex align-items-center">
                                        <div>
                                            <div class="text-muted font-weight-bolder">{{__("Documentation")}}</div>
                                            <p class="m-0 text-muted">{{__("Visit the official product docs")}}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mb-4 col-md-4">
                    <div class="card shadow rounded p-5">
                        <div class="card-body text-muted font-weight-medium">
                            <a href="https://codecanyon.net/item/justfans-premium-content-creators-saas-platform/35154898#item-description__changelog" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center  info-category-bg">
                                        <div class="icon voyager-file-code info-category-icon"></div>
                                    </div>
                                    <div class="ml-4 d-flex align-items-center">
                                        <div>
                                            <div class="text-muted font-weight-bolder">{{__("Changelog")}}</div>
                                            <p class="m-0 text-muted">{{__("Visit the official product changelog")}}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
@stop


<style>.card.bg-danger {
    border-radius: 8px;
    font-size: 16px;
}
.card .list-group-item {
    border: none;
}
</style>