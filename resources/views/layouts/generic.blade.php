<!doctype html>
<html class="h-100" dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}">
<head>
    @include('template.head')
</head>
<body class="d-flex flex-column">
<script>
function send_initial_checkout_pixels(){
    console.log("tracking InitiateCheckout...");
    @if(Route::is('profile') )
        @if( isset($pixel_user) )
            @if( !empty($pixel_user['meta-head']) )
                fbq('track', 'InitiateCheckout');
            @endif

            @if( !empty($pixel_user['google-head']) )
                gtag('event', 'InitiateCheckout');
            @endif

            @if( !empty($pixel_user['tiktok-head']) )
                ttq.track('InitiateCheckout')
            @endif

            @if( !empty($pixel_user['twitter-head']) )
                twq('track','InitiateCheckout');
            @endif


            
        @endif
    @endif
}
</script>
@include('elements.impersonation-header')
@include('template.header')
<div class="flex-fill">
    @yield('content')
</div>
@if(getSetting('compliance.enable_age_verification_dialog'))
    @include('elements.site-entry-approval-box')
@endif
@include('template.footer')
@include('template.jsVars')
@include('template.jsAssets')
@include('elements.language-selector-box')
</body>
</html>

