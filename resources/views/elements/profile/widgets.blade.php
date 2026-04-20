<div class="profile-widgets-area pb-3">

    <div class="card recent-media rounded-lg">
        <div class="card-body m-0 pb-0">
        </div>
        <h5 class="card-title pl-3 mb-0">{{__('Recent')}}</h5>
        <div class="card-body {{$recentMedia ?? 'text-center'}}">
            @if($recentMedia && count($recentMedia) && Auth::check())
                @foreach($recentMedia as $media)
                    <a href="{{$media->path}}" rel="mswp" title="">
                        <img src="{{ $media->thumbnail ?? $media->path }}" class="rounded mb-2 mb-md-2 mb-lg-2 mb-xl-0 img-fluid">
                    </a>
                @endforeach
            @else
                <p class="m-0">{{__('Latest media not available.')}}</p>
            @endif

        </div>
    </div>

    @if($user->paid_profile && (!getSetting('profiles.allow_users_enabling_open_profiles') || (getSetting('profiles.allow_users_enabling_open_profiles') && !$user->open_profile)))
        @if(Auth::check())
            @if( !(isset($hasSub) && $hasSub) && !(isset($post) && PostsHelper::hasActiveSub(Auth::user()->id, $post->user->id)) && Auth::user()->id !== $user->id)
                <div class="card mt-3 rounded-lg">
                    <div class="card-body">
                        <h5 class="card-title">{{__('Subscription')}}</h5>
                            <button onclick="redirectToCheckout()" class="btn btn-round btn-lg btn-primary btn-block mt-3 mb-2 text-center"  if(!Auth::check())>
                                <span>{{__('Subscribe')}}</span>
                            </button>
                    </div>
                </div>
            @endif
        @else
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">{{__('Subscription')}}</h5>
                    <button class="btn btn-round btn-lg btn-primary btn-block mt-3 mb-2 text-center"
                            data-toggle="modal"
                            data-target="#login-dialog"
                    >
                        <span class="d-none d-md-block d-xl-block d-lg-block">{{__('Subscribe')}}</span>
                    </button>
                </div>
            </div>
        @endif
    @elseif(!Auth::check() || (Auth::check() && Auth::user()->id !== $user->id))
        @if(Auth::check())
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">{{__('Follow this creator')}}</h5>
                    <button class="btn btn-round btn-outline-primary btn-block mt-3 mb-0 manage-follow-button" onclick="Lists.manageFollowsAction('{{$user->id}}')">
                        <span class="manage-follows-text">{{\App\Providers\ListsHelperServiceProvider::getUserFollowingType($user->id, true)}}</span>
                    </button>
                </div>
            </div>
        @else
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">{{__('Follow this creator')}}</h5>
                    <button class="btn btn-round btn-outline-primary btn-block mt-3 mb-0 text-center"
                            data-toggle="modal"
                            data-target="#login-dialog"
                    >
                        <span class="d-none d-md-block d-xl-block d-lg-block">{{__('Follow')}}</span>
                    </button>
                </div>
            </div>
        @endif
    @endif

    @if(getSetting('custom-code-ads.sidebar_ad_spot'))
        <div class="mt-3">
            {!! getSetting('custom-code-ads.sidebar_ad_spot') !!}
        </div>
    @endif

    @include('template.footer-feed')

</div>
