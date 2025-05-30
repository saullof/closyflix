<!doctype html>
<html class="h-100" dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}">
<head>
    @include('template.head',['additionalCss' => [
                '/libs/animate.css/animate.css',
                '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css',
                '/css/side-menu.css',
             ]])
</head>
<body class="d-flex flex-column">
<script>
    @if(Route::is('profile') )
        @if( isset($last_transaction) )
        if(localStorage.getItem("{{$last_transaction->id}}") != "1"){
            localStorage.setItem("{{$last_transaction->id}}", "1");
            @if( isset($pixel_user) )
                @if( !empty($pixel_user['meta-head']) )
                fbq("track", "Purchase", {
                    value: {{$last_transaction->amount}},
                    currency: "BRL",
                });
                @endif

                @if( !empty($pixel_user['google-head']) )
                    gtag('event', 'purchase', {
                        transaction_id: "{{$last_transaction->id}}",
                        value: {{$last_transaction->amount}},
                        currency: "BRL"
                    });
                @endif

                @if( !empty($pixel_user['tiktok-head']) )
                    ttq.track('Purchase', {content_type: 'product', value: {{$last_transaction->amount}}, currency: "BRL"})
                @endif

                @if( !empty($pixel_user['twitter-head']) )
                    twq('track','Purchase', {order_id: '{{$last_transaction->id}}', value: {{$last_transaction->amount}}, currency: "BRL", num_items: 1});
                @endif
            @endif
        }
        @endif
    @endif
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
<div class="flex-fill">
    @include('template.user-side-menu')

    <div class="container-xl overflow-x-hidden-m">
        <div class="row main-wrapper">
            <div class="col-2 col-md-3 pt-4 p-0 d-none d-md-block">
                @include('template.side-menu')
            </div>
            <div class="col-12 col-md-9 min-vh-100 border-left px-0 overflow-x-hidden-m content-wrapper {{(in_array(Route::currentRouteName(),['feed','profile','my.messenger.get','search.get','my.notifications','my.bookmarks','my.lists.all','my.lists.show','my.settings','posts.get']) ? '' : 'border-right' )}}">
                @yield('content')
            </div>
        </div>
        <div class="d-block d-md-none fixed-bottom">
            @include('elements.mobile-navbar')
        </div>
    </div>

</div>
@if(getSetting('compliance.enable_age_verification_dialog'))
    @include('elements.site-entry-approval-box')
@endif
@include('template.footer-compact',['compact'=>true])
@include('template.jsVars')
@include('template.jsAssets',['additionalJs' => [
               '/libs/jquery-backstretch/jquery.backstretch.min.js',
               '/libs/wow.js/dist/wow.min.js',
               '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js',
               '/js/SideMenu.js'
]])
@include('elements.language-selector-box')
</body>
</html>
