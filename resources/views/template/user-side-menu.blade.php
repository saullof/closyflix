<nav class="sidebar {{ Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'light') : (Cookie::get('app_theme') == 'dark' ? '' : 'light') }}">
    <!-- close sidebar menu -->
    <div class="col-12 pb-1">
        <div class="dismiss d-flex justify-content-center align-items-center flex-row">
            @include('elements.icon', ['icon' => 'arrow-back', 'variant' => 'medium'])
        </div>
    </div>

    <div class="col-12 sidebar-wrapper">
        <div class="mb-4 d-flex flex-row-no-rtl">
            <div>
                @if(Auth::check())
                    <img src="{{ Auth::user()->avatar }}" class="rounded-circle user-avatar">
                @else
                    <div class="avatar-placeholder">
                        @include('elements.icon', ['icon' => 'person-circle', 'variant' => 'xlarge'])
                    </div>
                @endif
            </div>
            <div class="pl-2 d-flex justify-content-center flex-column">
                @if(Auth::check())
                    <div><span><span>@</span>{{ Auth::user()->username }}</span></div>
                    <small class="p-0 m-0">{{ trans_choice('fans', Auth::user()->fansCount, ['number' => count(ListsHelper::getUserFollowers(Auth::user()->id))]) }} - {{ trans_choice('following', Auth::user()->followingCount, ['number' => Auth::user()->followingCount]) }}</small>
                @endif
            </div>
        </div>
    </div>

    <ul class="list-unstyled menu-elements p-0">
        @if(GenericHelper::isEmailEnforcedAndValidated())
            <li class="{{ Route::currentRouteName() == 'profile' && (request()->route('username') == Auth::user()->username) ? 'active' : '' }}">
                <a class="scroll-link d-flex align-items-center" href="{{ route('profile', ['username' => Auth::user()->username]) }}">
                    @include('elements.icon', ['icon' => 'person-circle-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __('My profile') }}
                </a>
            </li>
            @if(getSetting('streams.allow_streams'))
                <li class="{{ in_array(Route::currentRouteName(), ['my.streams.get', 'public.stream.get', 'public.vod.get']) ? 'active' : '' }}">
                    <a class="scroll-link d-flex align-items-center" href="{{ route('my.streams.get') }}">
                        @include('elements.icon', ['icon' => 'play-circle-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                        {{ __('Streams') }}
                    </a>
                </li>
            @endif
            <li class="{{ Route::currentRouteName() == 'my.bookmarks' ? 'active' : '' }}">
                <a class="scroll-link d-flex align-items-center" href="{{ route('my.bookmarks') }}">
                    @include('elements.icon', ['icon' => 'bookmarks-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __('Bookmarks') }}
                </a>
            </li>
            <li class="{{ Route::currentRouteName() == 'my.lists.all' ? 'active' : '' }}">
                <a class="scroll-link d-flex align-items-center" href="{{ route('my.lists.all') }}">
                    @include('elements.icon', ['icon' => 'list', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __('Lists') }}
                </a>
            </li>

            @if(Auth::check() && Auth::user()->email_verified_at && Auth::user()->birthdate && (Auth::user()->verification && Auth::user()->verification->status == 'verified'))
                <li class="{{ Route::currentRouteName() == 'coupons.index' ? 'active' : '' }}">
                    <a class="scroll-link d-flex align-items-center" href="{{ route('coupons.index') }}">
                        @include('elements.icon', ['icon' => 'bag-remove-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                        {{ __('Cupons') }}
                    </a>
                </li>
            @endif


            <li class="{{ Route::currentRouteName() == 'my.settings' ? 'active' : '' }}">
                <a class="scroll-link d-flex align-items-center" href="{{ route('my.settings') }}">
                    @include('elements.icon', ['icon' => 'settings-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __('Settings') }}
                </a>
            </li>
            <div class="menu-divider"></div>
        @endif
        <li>
            <a class="scroll-link d-flex align-items-center" href="{{ route('pages.get', ['slug' => 'help']) }}">
                @include('elements.icon', ['icon' => 'help-circle-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                {{ __('Help and support') }}
            </a>
        </li>
        @if(getSetting('site.allow_theme_switch'))
            <li>
                <a class="scroll-link d-flex align-items-center dark-mode-switcher" href="#">
                    @if(Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark'))
                        @include('elements.icon', ['icon' => 'contrast-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                        {{ __('Light mode') }}
                    @else
                        @include('elements.icon', ['icon' => 'contrast', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                        {{ __('Dark mode') }}
                    @endif
                </a>
            </li>
        @endif
        @if(getSetting('site.allow_direction_switch'))
            <li>
                <a class="scroll-link d-flex align-items-center rtl-mode-switcher" href="#">
                    @include('elements.icon', ['icon' => 'return-up-back', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __("RTL") }}
                </a>
            </li>
        @endif
        @if(getSetting('site.allow_language_switch'))
            <li>
                <a href="#otherSections" class="d-flex align-items-center" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle" role="button" aria-controls="otherSections">
                <div style="margin-right: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0,0,256,256" width="24px" height="24px">
                        <g fill="#888" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                            <g transform="scale(2,2)">
                                <path d="M117.8,31.22c-0.03,-0.04 -0.07,-0.08 -0.1,-0.12c-11.09,-18.04 -31.01,-30.1 -53.7,-30.1c-22.69,0 -42.61,12.06 -53.7,30.11c-0.03,0.04 -0.07,0.07 -0.1,0.11c-0.12,0.18 -0.22,0.36 -0.3,0.55c-5.65,9.43 -8.9,20.46 -8.9,32.23c0,34.74 28.26,63 63,63c34.74,0 63,-28.26 63,-63c0,-11.77 -3.25,-22.79 -8.9,-32.23c-0.08,-0.19 -0.18,-0.38 -0.3,-0.55zM111.2,32.09c-6.87,4.45 -14.27,7.87 -22.01,10.2c-3.22,-12.74 -9.29,-24.6 -17.87,-34.8c16.56,2.13 30.92,11.38 39.88,24.6zM85.9,64c0,4.85 -0.44,9.62 -1.26,14.3c-6.72,-1.52 -13.63,-2.3 -20.64,-2.3c-7.05,0 -13.99,0.79 -20.73,2.32c-0.83,-4.68 -1.27,-9.47 -1.27,-14.32c0,-4.85 0.44,-9.64 1.27,-14.32c6.74,1.53 13.68,2.32 20.73,2.32c7.01,0 13.92,-0.78 20.63,-2.3c0.82,4.68 1.27,9.46 1.27,14.3zM83.4,43.83c-6.31,1.43 -12.81,2.17 -19.4,2.17c-6.63,0 -13.16,-0.74 -19.5,-2.19c3.34,-13.22 9.96,-25.42 19.44,-35.61c9.49,10.19 16.12,22.4 19.46,35.63zM56.56,7.5c-8.56,10.19 -14.63,22.03 -17.85,34.76c-7.71,-2.33 -15.07,-5.74 -21.91,-10.17c8.94,-13.18 23.25,-22.42 39.76,-24.59zM7,64c0,-9.67 2.43,-18.78 6.7,-26.77c7.41,4.78 15.4,8.43 23.74,10.91c-0.94,5.19 -1.44,10.48 -1.44,15.86c0,5.37 0.5,10.67 1.44,15.86c-8.35,2.48 -16.33,6.13 -23.74,10.91c-4.27,-7.99 -6.7,-17.1 -6.7,-26.77zM16.8,95.91c6.84,-4.43 14.21,-7.84 21.91,-10.18c3.22,12.72 9.29,24.57 17.85,34.76c-16.51,-2.16 -30.82,-11.4 -39.76,-24.58zM63.95,119.8c-9.48,-10.19 -16.11,-22.39 -19.44,-35.61c6.33,-1.45 12.86,-2.19 19.49,-2.19c6.59,0 13.09,0.74 19.4,2.17c-3.33,13.23 -9.96,25.44 -19.45,35.63zM71.33,120.51c8.58,-10.2 14.65,-22.06 17.87,-34.8c7.74,2.34 15.13,5.75 22,10.2c-8.96,13.21 -23.31,22.47 -39.87,24.6zM114.3,90.77c-7.44,-4.8 -15.46,-8.46 -23.84,-10.94c0.94,-5.17 1.44,-10.46 1.44,-15.83c0,-5.36 -0.5,-10.65 -1.44,-15.83c8.38,-2.48 16.4,-6.14 23.84,-10.94c4.27,7.99 6.7,17.1 6.7,26.77c0,9.67 -2.43,18.78 -6.7,26.77z"></path>
                            </g>
                        </g>
                    </svg>
                </div>
                    {{ __('Language') }}
                </a>
                <ul class="collapse list-unstyled" id="otherSections">
                    @foreach(LocalesHelper::getAvailableLanguages() as $languageCode)
                        <li>
                            <a class="scroll-link d-flex align-items-center" href="{{ route('language', ['locale' => $languageCode]) }}">{{ __(LocalesHelper::getLanguageName($languageCode)) }}</a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif
        <div class="menu-divider"></div>
        <li>
            @if(Auth::check())
                <a class="scroll-link d-flex align-items-center pointer-cursor" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                    @include('elements.icon', ['icon' => 'log-out-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __('Log out') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @else
                <a class="scroll-link d-flex align-items-center" href="{{ route('login') }}">
                    @include('elements.icon', ['icon' => 'log-in-outline', 'variant' => 'medium', 'centered' => false, 'classes' => 'mr-2'])
                    {{ __('Login') }}
                </a>
            @endif
        </li>
    </ul>
</nav>


<!-- Dark overlay -->
<div class="overlay"></div>
