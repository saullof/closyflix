@extends('layouts.user-no-nav')

@section('page_title', isset($coupon) ? __('Editar Cupom') : __('Criar Cupom'))

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
            '/js/pages/coupons.js'
         ])->withFullUrl() 
    !!}
@stop

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ isset($coupon) ? __('Editar Cupom') : __('Criar Cupom') }}</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ isset($coupon) ? route('coupons.update', $coupon->id) : route('coupons.store') }}">
                            @csrf
                            @if(isset($coupon))
                                @method('PUT')
                            @endif

                            @if($errors->has('stripe_error'))
                                <div class="alert alert-danger">
                                    {{ $errors->first('stripe_error') }}
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="coupon_code">{{ __('Código do Cupom') }}</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="coupon_code" 
                                    name="coupon_code" 
                                    value="{{ old('coupon_code', $coupon->coupon_code ?? '') }}" 
                                    required
                                    maxlength="20" 
                                    pattern="[A-Z0-9\-_]+" 
                                    title="Somente letras maiúsculas, números, hífens e underscores"
                                    style="text-transform: uppercase;"
                                    oninput="this.value = this.value.toUpperCase();"
                                >
                                <small class="form-text text-muted">{{ __('Máximo 20 caracteres (maiúsculas, números, - e _)') }}</small>
                                @error('coupon_code')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <!-- Seleção do tipo de desconto -->
                            <div class="form-group">
                                <label for="discount_type">{{ __('Tipo de Desconto') }}</label>
                                <select id="discount_type" name="discount_type" class="form-control" required>
                                    <option value="percent" {{ old('discount_type', isset($coupon) && isset($coupon->discount_percent) ? 'percent' : 'percent') == 'percent' ? 'selected' : '' }}>
                                        {{ __('Percentual') }}
                                    </option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>
                                        {{ __('Valor Fixo') }}
                                    </option>
                                </select>
                            </div>

                            <!-- Campo para desconto percentual -->
                            <div id="discount_percent_div" class="form-group">
                                <label for="discount_percent_visible">{{ __('Percentual de Desconto') }} (%)</label>
                                @php
                                    $discountVisible = '';
                                    if(old('discount_percent') !== null) {
                                        $discountVisible = old('discount_percent') * 100;
                                    } elseif(isset($coupon) && $coupon->discount_percent !== null) {
                                        $discountVisible = $coupon->discount_percent * 100;
                                    }
                                @endphp
                                <input 
                                    type="number" 
                                    step="0.01"
                                    min="0.01" 
                                    max="100" 
                                    class="form-control" 
                                    id="discount_percent_visible" 
                                    placeholder="{{ __('Ex: 10 para 10%') }}"
                                    value="{{ $discountVisible }}"
                                    required
                                >
                                <small class="form-text text-muted">{{ __('Digite o valor em porcentagem (ex: 10 para 10%)') }}</small>
                                @error('discount_percent')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <input type="hidden" name="discount_percent" id="discount_percent" value="{{ old('discount_percent', isset($coupon) ? $coupon->discount_percent : '') }}">
                            </div>

                            <!-- Campo para desconto valor fixo -->
                            <div id="amount_off_div" class="form-group" style="display: none;">
                                <label for="amount_off_visible">{{ __('Valor do Desconto') }}</label>
                                @php
                                    $amountOffVisible = '';
                                    if(old('amount_off') !== null) {
                                        $amountOffVisible = old('amount_off') / 100;
                                    } elseif(isset($coupon) && isset($coupon->amount_off)) {
                                        $amountOffVisible = $coupon->amount_off / 100;
                                    }
                                @endphp
                                <input 
                                    type="number" 
                                    step="0.01"
                                    min="0.01" 
                                    class="form-control" 
                                    id="amount_off_visible" 
                                    placeholder="{{ __('Ex: 10 para R$10,00') }}"
                                    value="{{ $amountOffVisible }}"
                                    required
                                >
                                <small class="form-text text-muted">{{ __('Digite o valor do desconto. Será convertido para centavos.') }}</small>
                                @error('amount_off')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <input type="hidden" name="amount_off" id="amount_off" value="{{ old('amount_off', isset($coupon) ? $coupon->amount_off : '') }}">
                            </div>

                            <!-- Opções para o Tipo de Expiração -->
                            <div class="form-group">
                                <label for="expiration_type">{{ __('Tipo de Expiração') }}</label>
                                <select class="form-control" id="expiration_type" name="expiration_type" required>
                                    <option value="never" {{ old('expiration_type', $coupon->expiration_type ?? '') == 'never' ? 'selected' : '' }}>
                                        {{ __('Nunca expira') }}
                                    </option>
                                    <option value="usage" {{ old('expiration_type', $coupon->expiration_type ?? '') == 'usage' ? 'selected' : '' }}>
                                        {{ __('Uso limitado') }}
                                    </option>
                                    <option value="date" {{ old('expiration_type', $coupon->expiration_type ?? '') == 'date' ? 'selected' : '' }}>
                                        {{ __('Data específica') }}
                                    </option>
                                </select>
                                
                            </div>

                            <!-- Campo para Limite de Usos (apenas para "Uso limitado") -->
                            <div class="form-group" id="usage_limit_group">
                                <label for="usage_limit">{{ __('Limite de Usos') }}</label>
                                <input type="number" class="form-control" id="usage_limit" 
                                       name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}"
                                       min="1">
                                @error('usage_limit')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Campo de Duração (se for necessário; se não, remova) -->
                            <div class="form-group" id="duration_months_group">
                                <label for="duration_in_months">{{ __('Duração em Meses') }}</label>
                                <input type="number" class="form-control" id="duration_in_months" 
                                    name="duration_in_months" 
                                    value="{{ old('duration_in_months', $coupon->duration_in_months ?? '') }}"
                                    min="1" max="12">
                                <small class="form-text text-danger">Tempo de duração do desconto aplicado (Máximo de 12 meses)</small>
                                @error('duration_in_months')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Campo para Data de Expiração (apenas para "Data específica") -->
                            <div class="form-group" id="expires_at_group">
                                <label for="expires_at">{{ __('Data de Expiração') }}</label>
                                <input type="date" class="form-control" id="expires_at" 
                                       name="expires_at" value="{{ old('expires_at', isset($coupon->expires_at) ? $coupon->expires_at->format('Y-m-d') : '') }}"
                                       min="{{ now()->addDay()->format('Y-m-d') }}">
                                <small class="form-text text-danger">Tempo de duração do cupom ativo</small>
                                @error('expires_at')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="payment_method">{{ __('Método de Pagamento') }}</label>
                                <select id="payment_method" name="payment_method" class="form-control" required>
                                    <option value="all"
                                        {{ old('payment_method', isset($coupon) ? $coupon->payment_method : 'all') == 'all' ? 'selected' : '' }}>
                                        {{ __('Todos os pagamentos') }}
                                    </option>
                                    <option value="credit_card"
                                        {{ old('payment_method', isset($coupon) ? $coupon->payment_method : '') == 'credit_card' ? 'selected' : '' }}>
                                        {{ __('Cartão de Crédito') }}
                                    </option>
                                    <option value="pix"
                                        {{ old('payment_method', isset($coupon) ? $coupon->payment_method : '') == 'pix' ? 'selected' : '' }}>
                                        {{ __('Pix') }}
                                    </option>
                                </select>
                                @error('payment_method')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">
                                    {{ __('Escolha para quais métodos de pagamento o cupom será válido.') }}
                                </small>
                            </div>


                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    {{ isset($coupon) ? __('Atualizar Cupom') : __('Criar Cupom') }}
                                </button>
                                <a href="{{ route('coupons.index') }}" class="btn btn-secondary">
                                    {{ __('Cancelar') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
