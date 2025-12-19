<div class="mobile-bottom-nav border-top z-index-3 py-1 neutral-bg">
    <div class="d-flex justify-content-between w-100 py-2 px-2">
        <a href="{{Auth::check() ? route('feed') : route('home')}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{Route::currentRouteName() == 'feed' ? 'active' : ''}}">
            <div class="d-flex justify-content-center align-items-center">
                <div class="icon-wrapper d-flex justify-content-center align-items-center">
                    @include('elements.icon',['icon'=>'home-outline','variant'=>'large'])
                </div>
            </div>
        </a>
        @if(Auth::check())
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
                <span class="d-none d-md-block d-xl-block d-lg-block ml-2 text-truncate side-menu-label">{{__('Search')}}</span>
            </div>
        </a>
            @if(!getSetting('site.hide_create_post_menu'))
                @if(GenericHelper::isEmailEnforcedAndValidated())
                    <a href="{{route('posts.create')}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{Route::currentRouteName() == 'posts.create' ? 'active' : ''}}">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="icon-wrapper d-flex justify-content-center align-items-center">
                                @include('elements.icon',['icon'=>'add-circle-outline','variant'=>'large'])
                            </div>
                        </div>
                    </a>
                @endif
            @endif
            <a href="{{route('my.messenger.get')}}" class="h-pill h-pill-primary nav-link d-flex justify-content-between px-3 {{Route::currentRouteName() == 'my.messenger.get' ? 'active' : ''}}">
                <div class="d-flex justify-content-center align-items-center">
                    <div class="icon-wrapper d-flex justify-content-center align-items-center position-relative">
                        @include('elements.icon',['icon'=>'chatbubble-outline','variant'=>'large'])
                        <div class="menu-notification-badge chat-menu-count {{(NotificationsHelper::getUnreadMessages() > 0) ? '' : 'd-none'}}">
                            {{NotificationsHelper::getUnreadMessages()}}
                        </div>
                    </div>
                </div>
            </a>
        @endif
        <a href="javascript:void(0)" class="open-menu h-pill h-pill-primary nav-link d-flex justify-content-between px-3">
            <div class="d-flex justify-content-center align-items-center">
                <div class="icon-wrapper d-flex justify-content-center align-items-center">
                    @if(Auth::check())
                        <img src="{{Auth::user()->avatar}}" class="rounded-circle user-avatar w-32">
                    @else
                        <div class="avatar-placeholder">
                            @include('elements.icon',['icon'=>'person-circle','variant'=>'large'])
                        </div>
                    @endif
                </div>
            </div>
        </a>
    </div>
</div>
