@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <!-- Cabeçalho e botão -->
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Cupons de desconto</h2>
                    <a href="{{ route('coupons.create') }}" class="btn btn-grow btn-lg btn-primary bg-gradient-primary">
                        + Novo cupom de desconto
                    </a>
                </div>

                <!-- Cupons Ativos -->
                <div class="mb-12">
                    <h3 class="text-lg font-semibold mb-4">Cupons ativos</h3>
                    
                    <!-- Lista de Cupons -->
                    <div class="space-y-4">
                        @foreach($coupons as $coupon)
                        <div class="border rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <span class="font-bold text-lg">{{ $coupon->code }}</span>
                                    <div class="text-green-600 font-semibold">{{ $coupon->discount }}% OFF</div>
                                    <span class="inline-block bg-gray-100 px-2 py-1 rounded text-sm">
                                        @if($coupon->usage_count === 0)
                                        Cupom ainda não foi utilizado
                                        @else
                                        Utilizado {{ $coupon->usage_count }} vezes
                                        @endif
                                    </span>
                                </div>
                                
                                <div class="space-y-2">
                                    <input type="text" id="coupon-{{ $coupon->id }}" class="hidden" value="{{ $coupon->shareable_link }}">
                                    <button onclick="copyLink('coupon-{{ $coupon->id }}')" class="btn btn-grow btn-lg btn-secondary bg-gradient-secondary">
                                        Copiar link com desconto
                                    </button>
                                    <a href="#" class="block text-primary text-gradient font-weight-bold">Ver mais</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Histórico -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Histórico</h3>
                    <!-- Adicionar listagem histórica aqui -->
                    <div class="text-gray-400 text-sm">
                        Histórico de cupons expirados ou utilizados...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyLink(elementId) {
    const copyText = document.getElementById(elementId);
    copyText.select();
    document.execCommand('copy');
    alert('Link copiado para a área de transferência!');
}
</script>
@endsection