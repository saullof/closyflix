<meta charset="utf-8">

{{-- Page title --}}
@hasSection('page_title')
    <title>@yield('page_title') - {{getSetting('site.name')}} </title>
@else
    <title>{{getSetting('site.name')}} -  {{getSetting('site.slogan')}}</title>
@endif

{{-- Generic Meta tags --}}
@hasSection('page_description')
    <meta name="description" content="@yield('page_description')">
@endif

{{-- Mobile tab color --}}
<meta name="theme-color" content="#505050">
<meta name="color-scheme" content="dark light">

{{-- Facebook share section --}}
<meta property="og:url"           content="@yield('share_url')" />
<meta property="og:type"          content="@yield('share_type')" />
<meta property="og:title"         content="@yield('share_title')" />
<meta property="og:description"   content="@yield('share_description')" />
<meta property="og:image"         content="@yield('share_img')" />

{{-- Twitter share section --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@yield('share_url')">
<meta name="twitter:creator" content="@yield('author')">
<meta name="twitter:title" content="@yield('share_title')">
<meta name="twitter:description" content="@yield('share_description')">
<meta name="twitter:image" content="@yield('share_img')">

{{-- CSRF Baby --}}
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@yield('meta')

{!! getSetting('pixels.meta_head') !!}

{!! getSetting('pixels.twitter_head') !!}

{!! getSetting('pixels.google_head') !!}

{!! getSetting('pixels.tiktok_head') !!}

<script>
    (function() {
        var ATTRIBUTION_KEY = 'closyAbleAttribution';

        function getStoredAttribution() {
            try {
                return JSON.parse(localStorage.getItem(ATTRIBUTION_KEY) || '{}');
            } catch (e) {
                return {};
            }
        }

        function persistAttribution(data) {
            try {
                localStorage.setItem(ATTRIBUTION_KEY, JSON.stringify(data));
            } catch (e) {
                // swallow storage errors (private mode, quota, etc.)
            }
        }

        function captureAttribution() {
            var params = new URLSearchParams(window.location.search || '');
            var hasChanges = false;
            var stored = getStoredAttribution();
            var current = Object.assign({}, stored);

            ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid'].forEach(function(key) {
                var value = params.get(key);
                if (value) {
                    current[key] = value;
                    hasChanges = true;
                }
            });

            if (!current.first_seen_at) {
                current.first_seen_at = new Date().toISOString();
                hasChanges = true;
            }

            if (!current.referrer && document.referrer) {
                current.referrer = document.referrer;
                hasChanges = true;
            }

            if (Object.keys(current).length) {
                current.last_seen_at = new Date().toISOString();
            }

            if (hasChanges) {
                persistAttribution(current);
            }

            window.closyAbleAttribution = current;
        }

        function withAttribution(payload) {
            var attribution = window.closyAbleAttribution || {};
            if (attribution && Object.keys(attribution).length > 0) {
                return Object.assign({}, payload || {}, { attribution: attribution });
            }

            return payload || {};
        }

        captureAttribution();

        window.closyAbleQueue = window.closyAbleQueue || [];
        window.closyAbleTrack = function(eventName, payload) {
            if (typeof window.uipe === 'function') {
                window.uipe('track', eventName, withAttribution(payload));
                return;
            }

            window.closyAbleQueue.push({eventName: eventName, payload: withAttribution(payload)});
        };

        var el = document.createElement('script');
        el.src = 'https://app.ablecdp.com/ue.js';
        el.async = true;
        el.addEventListener('load', function() {
            uipe('init', 'b7f75d8f-b251-4e68-ac06-b0ccba8a2217');
            uipe('track', 'PageView');

            var registeredEmail = @json(optional(auth()->user())->email);
            if (registeredEmail) {
                uipe('track', 'CompleteRegistration', {keys: {email: registeredEmail}});
            }

            if (Array.isArray(window.closyAbleQueue)) {
                while (window.closyAbleQueue.length > 0) {
                    var queued = window.closyAbleQueue.shift();
                    if (queued && queued.eventName) {
                        uipe('track', queued.eventName, queued.payload || {});
                    }
                }
            }
        });

        document.head.appendChild(el);
    })();
</script>

<!-- user pixels -->

@if(Route::is('profile') || Route::is('profile.checkout') || Route::is('checkout'))
    @if( isset($pixel_user) )
            <script>
                var loadTime = parseInt(localStorage.getItem("PageViewDone"));
                var currentTime = (new Date().getTime()) / 1000;
                var doPageView = false;
                if(loadTime == NaN){
                loadTime = currentTime;
                localStorage.setItem("PageViewDone", currentTime);
                }
                
                if ((currentTime - loadTime >= 300) || currentTime == loadTime) {
                    localStorage.setItem("PageViewDone", currentTime);
                    doPageView = true;
                }
            </script>
        @if( !empty($pixel_user['meta-head']) )
            <!-- Meta Pixel Code -->
            <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ e($pixel_user["meta-head"]) }}');
            if(doPageView){
                fbq('track', 'PageView');
            }
            </script>
            <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ e($pixel_user["meta-head"]) }}&ev=PageView&noscript=1"
            /></noscript>
            <!-- End Meta Pixel Code -->
        @endif


        @if( !empty($pixel_user['google-head']) )
            <!-- Google Pixel Code -->

            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={!!  $pixel_user['google-head'] !!}"></script>
            <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', '{!!  $pixel_user["google-head"] !!}');
            if(doPageView){
                gtag('event', 'screen_view', {
                    'app_name': 'closyflix',
                    'screen_name': 'Profile'
                });
            }
            </script>
            <!-- End Google Code -->
        @endif

        @if( !empty($pixel_user['tiktok-head']) )

            <script>
            !function (w, d, t) {
            w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
            }(window, document, 'ttq');

            ttq.load('{!!  $pixel_user["tiktok-head"] !!}');
            ttq.page();
            </script>
        @endif

        @if( !empty($pixel_user['twitter-head']) )
            <script>
                !function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);},s.version='1.1',s.queue= [],u=t.createElement(n),u.async=!0,u.src='//static.ads-twitter.com/uwt.js',a=t.getElementsByTagName(n) [0],a.parentNode.insertBefore(u,a))}(window,document,'script');
                twq('init','{!!  $pixel_user["twitter-head"] !!}');
                if(doPageView){
                    twq('track','PageView');
                }
            </script>
        @endif
    @endif
@endif

<!-- end user pixels -->

@if(getSetting('site.allow_pwa_installs'))
    @laravelPWA
    <script type="text/javascript">
        (function() {
            // Initialize the service worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/serviceworker.js', {
                    scope: '.'
                }).then(function (registration) {
                    // Registration was successful
                    // eslint-disable-next-line no-console
                    console.log('Laravel PWA: ServiceWorker registration successful with scope: ', registration.scope);
                }, function (err) {
                    // registration failed :(
                    // eslint-disable-next-line no-console
                    console.log('Laravel PWA: ServiceWorker registration failed: ', err);
                });
            }
        })();
    </script>
@endif
<script src="{{asset('libs/pusher-js/dist/web/pusher.min.js')}}"></script>

{{-- Favicon --}}
<link rel="shortcut icon" href="{{ getSetting('site.favicon') }}" type="image/x-icon">

{{-- (Preloading) Fonts --}}
<link rel="preload" href="{{ asset('fonts/OpenSans-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
<link rel="preload" href="{{ asset('fonts/OpenSans-Semibold.ttf') }}" as="font" type="font/ttf" crossorigin>
<link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
{{-- Global CSS Assets --}}
{!!
    Minify::stylesheet(
        array_merge([
            '/libs/cookieconsent/build/cookieconsent.min.css',
            '/css/theme/bootstrap'.
            (Cookie::get('app_rtl') == null ? (getSetting('site.default_site_direction') == 'rtl' ? '.rtl' : '') : (Cookie::get('app_rtl') == 'rtl' ? '.rtl' : '')).
            (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '.dark' : '') : (Cookie::get('app_theme') == 'dark' ? '.dark' : '')).
            '.css',
            '/css/app.css',
         ],
         (isset($additionalCss) ? $additionalCss : [])
         ))->withFullUrl()
!!}

{{-- Page specific CSS --}}
@yield('styles')

@if(getSetting('custom-code-ads.custom_css'))
    <style>
        {!! getSetting('custom-code-ads.custom_css') !!}
    </style>
@endif

