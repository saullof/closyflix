@extends('layouts.user-no-nav')

@section('page_title',  ucfirst(__($activeSettingsTab)))

@section('scripts')
    {!!
        Minify::javascript(
            array_merge($additionalAssets['js'],[
                '/js/pages/settings/settings.js',
                '/js/suggestions.js',
         ])
        )->withFullUrl()
    !!}
    @if(getSetting('profiles.allow_profile_bio_markdown'))
        <script src="{{asset('/libs/easymde/dist/easymde.min.js')}}"></script>
    @endif

    
@stop

@section('styles')
    {!!
        Minify::stylesheet(
            array_merge($additionalAssets['css'],[
                '/css/pages/settings.css',
                ])
         )->withFullUrl()
    !!}
    <style>
        .selectize-control.multi .selectize-input>div.active {
            background:#{{getSetting('colors.theme_color_code')}};
        }
    </style>
    @if(getSetting('profiles.allow_profile_bio_markdown'))
        <link href="{{asset('/libs/easymde/dist/easymde.min.css')}}" rel="stylesheet">
    @endif
@stop

@section('content')
    <div class="">
        <div class="row">
            <div class="col-12 col-md-4 col-lg-3 mb-3 pr-0 settings-menu">
                <div class="settings-menu-wrapper">
                    <div class="d-none d-md-block">
                        @include('elements.settings.settings-header',['type'=>'generic'])
                    </div>
                    <div class="d-block d-md-none mt-3">
                        <div class="d-flex justify-content-between">
                            <div>
                                @include('elements.settings.settings-header',['type'=>'settingTab'])
                            </div>
                            <div class="d-flex align-content-center pt-2">
                                <div style="color: #e93745;" class="w-40 navbar-toggler d-lg-none mr-4 h-pill h-pill-primary rounded d-flex justify-content-center align-items-center" data-toggle="collapse" data-target="#settingsNav" aria-controls="settingsNav" aria-expanded="true" aria-label="Toggle navigation">
                                    <div class="ion-icon-wrapper  icon-medium d-flex justify-content-center align-items-center">
                                    <div class="ion-icon-inner">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M368 128h80M64 128h240M368 384h80M64 384h240M208 256h240M64 256h80"></path><circle cx="336" cy="128" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"></circle><circle cx="176" cy="256" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"></circle><circle cx="336" cy="384" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"></circle></svg>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <hr class="mb-0">
                    <div class="">
                        @include('elements.settings.settings-menu',['availableSettings' => $availableSettings])
                    </div>
                    <div class="">
                        @include('elements.settings.settings-menu-mobile',['availableSettings' => $availableSettings])
                    </div>
                </div>
            </div>
            <div class="col-md-8 col-lg-9 mb-5 mb-lg-0 min-vh-100 border-left border-right settings-content mt-1 mt-md-0 pl-md-0 pr-md-0">
                <div class="ml-3 d-none d-md-flex justify-content-between">
                    <div>
                        <h5 class="text-bold mt-0 mt-md-3 mb-0 {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{ ucfirst(__($activeSettingsTab))}}</h5>
                        <h6 class="mt-2 text-muted">{{__($currentSettingTab['heading'])}}</h6>
                    </div>
                </div>
                <hr class="{{in_array($activeSettingsTab, ['subscriptions','payments']) ? 'mb-0' : ''}} d-none d-md-block mt-2">
                <div class="{{in_array($activeSettingsTab, ['subscriptions','payments', 'referrals']) ? '' : 'px-4 px-md-3'}}">
                    @include('elements.settings.settings-'.$activeSettingsTab)
                </div>
            </div>
        </div>
    </div>
@stop


