<div class="side-menu px-1 px-md-2 px-lg-3">
    <div class="user-details mb-4 d-flex open-menu pointer-cursor flex-row-no-rtl">
        <div class="ml-0 ml-md-2">
            @if(Auth::check())
                <img src="{{Auth::user()->avatar}}" class="rounded-circle user-avatar">
            @else
                <div class="avatar-placeholder">
                    @include('elements.icon',['icon'=>'person-circle','variant'=>'xlarge text-muted'])
                </div>
            @endif
        </div>
        @if(Auth::check())
            <div class="d-none d-lg-block overflow-hidden">
                <div class="pl-2 d-flex justify-content-center flex-column overflow-hidden">
                    <div class="ml-2 d-flex flex-column overflow-hidden">
                        <span class="text-bold text-truncate {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{Auth::user()->name}}</span>
                        <span class="text-muted"><span>@</span>{{Auth::user()->username}}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <ul class="nav flex-column user-side-menu">
        <li class="nav-item ">
            <a href="{{Auth::check() ? route('feed') : route('home')}}" class="h-pill h-pill-primary nav-link {{Route::currentRouteName() == 'feed' ? 'active' : ''}} d-flex justify-content-between">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center">
                        @include('elements.icon',['icon'=>'home-outline','variant'=>'large'])
                    </div>
                    <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Home')}}</span>
                </div>
            </a>
        </li>


        <li class="nav-item">
            <a href="https://closyflix.com/search?query=&filter=people" class="nav-link h-pill h-pill-primary d-flex justify-content-between search-link">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0,0,256,256" width="32px" height="32px">
                            <g fill="currentColor" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                                <g transform="scale(5.12,5.12)">
                                    <path d="M21,3c-9.39844,0 -17,7.60156 -17,17c0,9.39844 7.60156,17 17,17c3.35547,0 6.46094,-0.98437 9.09375,-2.65625l12.28125,12.28125l4.25,-4.25l-12.125,-12.09375c2.17969,-2.85937 3.5,-6.40234 3.5,-10.28125c0,-9.39844 -7.60156,-17 -17,-17zM21,7c7.19922,0 13,5.80078 13,13c0,7.19922 -5.80078,13 -13,13c-7.19922,0 -13,-5.80078 -13,-13c0,-7.19922 5.80078,-13 13,-13z"></path>
                                </g>
                            </g>
                        </svg>
                    </div>
                    <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Search')}}</span>
                </div>
            </a>
        </li>
        @if(Auth::check() && Auth::user()->email_verified_at && Auth::user()->birthdate && (Auth::user()->verification && Auth::user()->verification->status == 'verified'))
            <li class="nav-item">
                <a href="{{ route('coupons.index') }}" class="nav-link {{ Route::currentRouteName() == 'coupons.index' ? 'active' : '' }} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon', ['icon' => 'bag-remove-outline', 'variant' => 'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">
                            {{ __('Cupons') }}
                        </span>
                    </div>
                </a>
            </li>
        @endif



        @if(GenericHelper::isEmailEnforcedAndValidated())
            <li class="nav-item">
                <a href="{{route('my.notifications')}}" class="nav-link h-pill h-pill-primary {{Route::currentRouteName() == 'my.notifications' ? 'active' : ''}} d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                            @include('elements.icon',['icon'=>'notifications-outline','variant'=>'large'])
                            <div class="menu-notification-badge notifications-menu-count {{(isset($notificationsCountOverride) && $notificationsCountOverride->total > 0 ) || (NotificationsHelper::getUnreadNotifications()->total > 0) ? '' : 'd-none'}}">
                                {{!isset($notificationsCountOverride) ? NotificationsHelper::getUnreadNotifications()->total : $notificationsCountOverride->total}}
                            </div>
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Notifications')}}</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route('my.messenger.get')}}" class="nav-link {{Route::currentRouteName() == 'my.messenger.get' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                            @include('elements.icon',['icon'=>'chatbubble-outline','variant'=>'large'])
                            <div class="menu-notification-badge chat-menu-count {{(NotificationsHelper::getUnreadMessages() > 0) ? '' : 'd-none'}}">
                                {{NotificationsHelper::getUnreadMessages()}}
                            </div>
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Messages')}}</span>
                    </div>
                </a>
            </li>
            @if(getSetting('streams.allow_streams'))
                <li class="nav-item">
                    <a href="{{route('search.get')}}?filter=live" class="nav-link {{Route::currentRouteName() == 'search.get' && request()->get('filter') == 'live' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                                @include('elements.icon',['icon'=>'play-circle-outline','variant'=>'large'])
                                <div class="menu-notification-badge streams-menu-count {{(StreamsHelper::getPublicLiveStreamsCount() > 0) ? '' : 'd-none'}}">
                                    {{StreamsHelper::getPublicLiveStreamsCount()}}
                                </div>
                            </div>
                            <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Streams')}}</span>
                        </div>

                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a href="{{route('my.bookmarks')}}" class="nav-link {{Route::currentRouteName() == 'my.bookmarks' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'bookmark-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Bookmarks')}}</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route('my.lists.all')}}" class="nav-link {{Route::currentRouteName() == 'my.lists.all' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'list-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Lists')}}</span>
                    </div>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{route('my.settings',['type'=>'subscriptions'])}}" class="nav-link {{Route::currentRouteName() == 'my.settings' &&  is_int(strpos(Request::path(),'subscriptions')) ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'people-circle-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Subscriptions')}}</span>
                    </div>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{route('profile',['username'=>Auth::user()->username])}}" class="nav-link {{Route::currentRouteName() == 'profile' && (request()->route("username") == Auth::user()->username) ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'person-circle-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('My profile')}}</span>
                    </div>
                </a>
            </li>
        @endif

        @if(!Auth::check())
            <li class="nav-item">
                <a href="{{route('search.get')}}" class="nav-link {{Route::currentRouteName() == 'search.get' ? 'active' : ''}} h-pill h-pill-primary d-flex justify-content-between">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="icon-wrapper d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'compass-outline','variant'=>'large'])
                        </div>
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('Explore')}}</span>
                    </div>
                </a>
            </li>
        @endif

        <li class="nav-item">
            <a href="#" role="button" class="open-menu nav-link h-pill h-pill-primary text-muted d-flex justify-content-between">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center">
                        @include('elements.icon',['icon'=>'ellipsis-horizontal-circle-outline','variant'=>'large'])
                    </div>
                    <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label" style="font-size: 16px !important">{{__('More')}}</span>
                </div>
            </a>
        </li>

        @if(GenericHelper::isEmailEnforcedAndValidated())
            @if(getSetting('streams.allow_streams'))
                <li class="nav-item-live mt-2 mb-0">
                    <a role="button" class="btn btn-round btn-outline-danger btn-block px-3" href="{{route('my.streams.get')}}{{StreamsHelper::getUserInProgressStream() ? '' : ( !GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? '' : '?action=create')}}">
                        <div class="d-none d-md-flex d-xl-flex d-lg-flex justify-content-center align-items-center ml-1 text-truncate new-post-label">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div class="stream-on-label w-100 {{StreamsHelper::getUserInProgressStream() ? '' : 'd-none'}}">
                                    <div class="d-flex align-items-center w-100">
                                        <div class="mr-4"><div class="blob red"></div></div>
                                        <div class="ml-1">{{__('On air')}} </div>
                                    </div>
                                </div>
                                <div class="stream-off-label w-100 {{StreamsHelper::getUserInProgressStream() ? 'd-none' : ''}}">
                                    <div class="d-flex  align-items-center w-100">
                                        <div class="mr-3"> @include('elements.icon',['icon'=>'ellipse','variant'=>'','classes'=>'flex-shrink-0 text-danger'])</div>
                                        <div class="ml-1">{{__('Go live')}} </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="d-block d-md-none d-flex align-items-center justify-content-center">@include('elements.icon',['icon'=>'add-circle-outline','variant'=>'medium','classes'=>'flex-shrink-0'])</div>
                    </a>
                </li>
            @endif
        @endif

        @if(!getSetting('site.hide_create_post_menu'))
            @if(GenericHelper::isEmailEnforcedAndValidated())
                <li class="nav-item">
                    <a role="button" class="btn btn-round btn-primary btn-block " href="{{route('posts.create')}}">
                        <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate new-post-label">{{__('New post')}}</span>
                        <span class="d-block d-md-none d-flex align-items-center justify-content-center">@include('elements.icon',['icon'=>'add-circle-outline','variant'=>'medium','classes'=>'flex-shrink-0'])</span>
                    </a>
                </li>
            @endif
        @endif


    </ul>
</div>
