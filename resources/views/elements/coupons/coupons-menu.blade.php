@if($variant == 'desktop')
    <div class="card-settings border-bottom">
        <div class="list-group list-group-sm list-group-flush">
            <a href="{{ route('coupons.index', ['type' => 'active']) }}" class="{{ $activeTab == 'active' ? 'active' : '' }} list-group-item list-group-item-action d-flex justify-content-between">
                <div class="d-flex align-items-center">
                    @include('elements.icon',['icon'=>'pricetag-outline','centered'=>'false','classes'=>'mr-3','variant'=>'medium'])
                    <span>{{ __('Cupons Ativos') }}</span>
                </div>
                <div class="d-flex align-items-center">
                    @include('elements.icon',['icon'=>'chevron-forward-outline'])
                </div>
            </a>
            <a href="{{ route('coupons.index', ['type' => 'history']) }}" class="{{ $activeTab == 'history' ? 'active' : '' }} list-group-item list-group-item-action d-flex justify-content-between">
                <div class="d-flex align-items-center">
                    @include('elements.icon',['icon'=>'time-outline','centered'=>'false','classes'=>'mr-3','variant'=>'medium'])
                    <span>{{ __('Histórico') }}</span>
                </div>
                <div class="d-flex align-items-center">
                    @include('elements.icon',['icon'=>'chevron-forward-outline'])
                </div>
            </a>
        </div>
    </div>
@else
    <div class="mt-3 inline-border-tabs text-bold">
        <nav class="nav nav-pills nav-justified">
            <a class="nav-item nav-link {{ $activeTab == 'active' ? 'active' : '' }}" href="{{ route('coupons.index', ['type' => 'active']) }}">
                <div class="d-flex justify-content-center">
                    @include('elements.icon',['icon'=>'pricetag-outline','centered'=>'false','variant'=>'medium'])
                </div>
            </a>
            <a class="nav-item nav-link {{ $activeTab == 'history' ? 'active' : '' }}" href="{{ route('coupons.index', ['type' => 'history']) }}">
                <div class="d-flex justify-content-center">
                    @include('elements.icon',['icon'=>'time-outline','centered'=>'false','variant'=>'medium'])
                </div>
            </a>
        </nav>
    </div>
@endif