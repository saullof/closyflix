@extends('layouts.user-no-nav')

@section('page_title', __('Cupons'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/coupons.css',
            '/css/pages/checkout.css'
         ])->withFullUrl()
    !!}
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/pages/checkout.js',
            '/js/pages/coupons.js'
         ])->withFullUrl()
    !!}
@stop

@section('content')
    <div class="">
        <div class="row">

            <!-- Sidebar Menu -->
            <div class="col-12 col-md-6 col-lg-3 mb-3 settings-menu pr-0">
                <div class="bookmarks-menu-wrapper">
                    <div class="mt-3 ml-3">
                        <h5 class="text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{__('Cupons')}}</h5>
                    </div>
                    <hr class="mb-0">
                    <div class="d-lg-block bookmarks-nav">
                        <div class="d-none d-md-block">
                            @include('elements.coupons.coupons-menu', ['variant' => 'desktop'])
                        </div>
                        <div class="bookmarks-menu-mobile d-block d-md-none mt-3">
                            @include('elements.coupons.coupons-menu', ['variant' => 'mobile'])
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-12 col-md-6 col-lg-9 mb-5 mb-lg-0 min-vh-100 border-left border-right settings-content pl-md-0 pr-md-0">
                <div class="px-2 px-md-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">{{ __('Gerenciar Cupons') }}</h4>
                        <a href="{{ route('coupons.create') }}" class="btn btn-primary">
                            @include('elements.icon',['icon'=>'add-outline','classes'=>'mr-1'])
                        </a>
                    </div>

                    <div class="coupons-list">
                        @forelse($coupons as $coupon)
                        <div class="coupon-item mb-3 p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h5 class="mb-0 mr-3">{{ $coupon->code }}</h5>
                                        <span class="badge {{ $coupon->isActive() ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $coupon->isActive() ? __('Ativo') : __('Inativo') }}
                                        </span>
                                    </div>
                                    <div class="coupon-details">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <small class="text-muted">{{ __('Desconto') }}:</small>
                                                <div>
                                                    @if (!is_null($coupon->discount_percent))
                                                        {{ number_format($coupon->discount_percent * 100, 0) }}%
                                                    @elseif (!is_null($coupon->amount_off))
                                                        R$ {{ number_format($coupon->amount_off / 100, 2, ',', '.') }}
                                                    @else
                                                        {{ __('Sem desconto') }}
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <small class="text-muted">{{ __('Validade') }}:</small>
                                                <div>{{ $coupon->expires_at ? $coupon->expires_at->format('d/m/Y') : __('Indeterminada') }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted">{{ __('Usos') }}:</small>
                                                <div>{{ $coupon->times_used }} / {{ $coupon->usage_limit ?? '∞' }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted">{{ __('Cupom') }}:</small>
                                                <div>{{ $coupon->coupon_code }}</div>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control shareable-link" 
                                                           value="{{ url(auth()->user()->username . '/checkout/' . $coupon->coupon_code) }}" 
                                                           readonly
                                                           id="coupon-link-{{ $coupon->id }}">
                                                    <div class="input-group-append">
                                                    <button class="btn btn-primary copy-coupon-link" 
                                                            type="button"
                                                            data-coupon-id="{{ $coupon->id }}">
                                                        @include('elements.icon',['icon'=>'copy-outline','classes'=>'mr-1'])
                                                    </button>
                                                    </div>
                                                </div>
                                                <small class="text-muted mt-1 d-block">{{ __('Compartilhe este link para oferecer o cupom') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-3 d-flex align-items-center">
                                    <form action="{{ route('coupons.delete', $coupon->id) }}" method="POST" class="ml-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-link text-danger p-2" 
                                                style="font-size: 1.5rem;" 
                                                onclick="return confirm('Tem certeza que deseja excluir este cupom?')">
                                            @include('elements.icon',['icon'=>'trash-outline', 'classes'=>'text-danger', 'style' => 'font-size: 2rem;'])
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-5">
                            <h4>{{ __('Nenhum cupom encontrado') }}</h4>
                            <p class="mb-0">{{ __('Comece criando seu primeiro cupom') }}</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        {{ $coupons->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop