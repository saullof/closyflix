<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Função para carregar os dados ao carregar a página
        loadOverviewData();

        // Função para enviar o formulário via AJAX
        $('#periodFilterForm').on('submit', function (e) {
            e.preventDefault();
            loadOverviewData();
        });

        function loadOverviewData() {
            // Captura o valor do select
            const period = $('#period').val();
            console.log('Período selecionado:', period); // Adiciona o log no console

            $.ajax({
                url: "{{ route('overview') }}",
                method: "GET",
                data: $('#periodFilterForm').serialize(),
                success: function (response) {
                    // Atualizar o valor dos ganhos
                    $('#earningsAmount').text('R$ ' + response.totalEarningsFormatted);

                    // Atualizar a barra de progresso e os níveis de ranking
                    updateRankingProgress(response.totalEarnings);

                    // Atualizar a contagem de assinantes
                    $('#subscribersCount').text(response.activeSubscribersCount);
                },
                error: function () {
                    alert('Erro ao carregar os dados.');
                }
            });
        }

        function updateRankingProgress(totalEarnings) {
            let currentLevel = 'INÍCIO';
            let nextLevelThreshold = 10000; // Iniciar com o próximo nível para INÍCIO
            let currentRankImage = '/img/ranks/IRON.png'; // Imagem padrão para o nível INÍCIO

            // Definir o nível atual, o próximo nível e a imagem do ranking com base nos ganhos
            if (totalEarnings >= 1000000) {
                currentLevel = 'SUPREME';
                nextLevelThreshold = totalEarnings;
                currentRankImage = '/img/ranks/SUPREME.png';
            } else if (totalEarnings >= 500000) {
                currentLevel = 'PLATINUM';
                nextLevelThreshold = 1000000;
                currentRankImage = '/img/ranks/PLATINUM.png';
            } else if (totalEarnings >= 100000) {
                currentLevel = 'GOLD';
                nextLevelThreshold = 500000;
                currentRankImage = '/img/ranks/GOLD.png';
            } else if (totalEarnings >= 50000) {
                currentLevel = 'BRONZE';
                nextLevelThreshold = 100000;
                currentRankImage = '/img/ranks/BRONZE.png';
            } else if (totalEarnings >= 10000) {
                currentLevel = 'MASTER';
                nextLevelThreshold = 50000;
                currentRankImage = '/img/ranks/MASTER.png';
            }

            // Calcula a porcentagem de progresso para o próximo nível
            const progressPercent = Math.min((totalEarnings / nextLevelThreshold) * 100, 100);

            // Atualizar a barra de progresso com a porcentagem calculada
            $('#progressEarnings').css('width', progressPercent + '%');

            // Atualizar o texto do nível atual e próximo nível
            $('#currentLevel').text('Nível atual: ' + currentLevel);
            $('#nextLevel').text('Próximo nível: R$ ' + nextLevelThreshold.toLocaleString('pt-BR', { minimumFractionDigits: 2 }));

            // Atualizar a imagem do ranking atual
            $('#currentRankImage').attr('src', currentRankImage);
        }
    });
</script>

<div class="overview-section"
    style="background-color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#1c1c1c' : '#f8f9fa' }};
           color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#ffffff' : '#343a40' }};">
    <h2>Visão Geral</h2>




    <!-- Filtro de Período -->
    <div class="period-filter d-flex align-items-center" style="margin-bottom: 10px !important">
        <form id="periodFilterForm" class="period-filter d-flex align-items-center">
            <label for="period" class="mr-2">Período:</label>
            <select id="period" name="period" class="custom-select">
                <option value="all_time" {{ request('period', 'all_time') === 'all_time' ? 'selected' : '' }}>Todos os tempos</option>
                <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Hoje</option>
                <option value="7_days" {{ request('period') === '7_days' ? 'selected' : '' }}>7 dias</option>
                <option value="30_days" {{ request('period') === '30_days' ? 'selected' : '' }}>30 dias</option>
                <option value="60_days" {{ request('period') === '60_days' ? 'selected' : '' }}>60 dias</option>
                <option value="90_days" {{ request('period') === '90_days' ? 'selected' : '' }}>90 dias</option>
            </select>
            <input type="date" id="start-date" name="start_date" class="ml-2 d-none" placeholder="Data Inicial" value="{{ request('start_date') }}">
            <input type="date" id="end-date" name="end_date" class="ml-2 d-none" placeholder="Data Final" value="{{ request('end_date') }}">
            <button type="submit" class="btn btn-primary ml-3" style="margin: 0px !important; margin-left: 3px !important">Aplicar</button>
        </form>
    </div>

    <!-- Card de Ganhos -->
    <div class="card mb-4 earnings-card" style="background-color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#2c2c2c' : '#fff' }}; color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#fff' : '#000' }};">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Imagem do ranking atual -->

                <div class="card-title">Seus ganhos (líquido)</div>
                <div id="earningsAmount" class="earnings-amount" style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#E93745' : '#E93745' }}">
                    R$ {{ number_format($totalEarnings ?? 0, 2, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="ranking-system d-flex align-items-center flex-column"
            style="background-color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#2c2c2c' : '#fff' }}; 
                color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#fff' : '#000' }};">
            
            <div class="current-rank d-flex align-items-center mb-3">
                <img id="currentRankImage" src="/img/ranks/IRON.png" alt="Iron Rank" style="width: 150px; height: 150px; margin-right: 15px;">
                <h4 id="currentLevel" style="margin: 0;">Nível atual: INÍCIO</h4>
            </div>

            <div class="sales-progress w-100 mb-4">
                <!-- Barra de progresso baseada no total de ganhos -->
                <div class="progress-bar">
                    <div id="progressEarnings" class="progress earnings-amount" style="width: 0%; background-color: red;"></div>
                </div>
                <p id="nextLevel" class="text-right mt-2">Próximo nível: R$ 10k</p>
            </div>

            <!-- Níveis de Ranking -->
            <div class="rank-levels d-flex justify-content-around w-100">
                <div class="rank-item text-center">
                    <img src="/img/ranks/IRON.png" alt="Iron Rank">
                    <p style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#FFF' : '#000' }}">IRON: INÍCIO</p>
                </div>
                <div class="rank-item text-center">
                    <img src="/img/ranks/MASTER.png" alt="Master Rank">
                    <p style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#FFF' : '#000' }}">MASTER: R$ 10K</p>
                </div>
                <div class="rank-item text-center">
                    <img src="/img/ranks/BRONZE.png" alt="Bronze Rank">
                    <p style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#FFF' : '#000' }}">BRONZE: R$ 50K</p>
                </div>
                <div class="rank-item text-center">
                    <img src="/img/ranks/GOLD.png" alt="Gold Rank">
                    <p style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#FFF' : '#000' }}">GOLD: R$ 100K</p>
                </div>
                <div class="rank-item text-center">
                    <img src="/img/ranks/PLATINUM.png" alt="Platinum Rank">
                    <p style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#FFF' : '#000' }}">PLATINUM: R$ 500K</p>
                </div>
                <div class="rank-item text-center">
                    <img src="/img/ranks/SUPREME.png" alt="Supreme Rank">
                    <p style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#FFF' : '#000' }}">SUPREME: R$ 1M</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Card de Assinantes Ativos -->
    <div class="card mb-4 subscribers-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="card-title">Assinantes ativos (pagos)</div>
                <div id="subscribersCount" class="subscribers-count" style="color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#E93745' : '#E93745' }}">{{ $activeSubscribersCount ?? 0 }}</div>
            </div>
        </div>
    </div>

    <h2>Pagamentos</h2>

    @if(count($payments))
    <div class="d-flex flex-column align-items-center payments-wrapper">
        @foreach($payments as $payment)
            <div class="card mb-4 payment-card" 
                style="background-color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#1c1c1c' : '#f8f9fa' }};
                color: {{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? '#ffffff' : '#343a40' }};">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="sender-name">{{ $payment->sender->name }}</div>
                        <div class="payment-amount">{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($payment->amount) }}</div>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="payment-details">
                            <small>({{ ucfirst(__($payment->type)) }})</small>
                        </div>
                        <div class="payment-status">
                            @switch($payment->status)
                                @case('approved')
                                    <span class="badge" style="background-color: #28a745;">
                                        {{ ucfirst(__($payment->status)) }}
                                    </span>
                                    @break
                                @case('initiated')
                                @case('pending')
                                    <span class="badge" style="background-color: #17a2b8;">
                                        {{ ucfirst(__($payment->status)) }}
                                    </span>
                                    @break
                                @case('canceled')
                                @case('refunded')
                                    <span class="badge" style="background-color: #ffc107;">
                                        {{ ucfirst(__($payment->status)) }}
                                    </span>
                                    @break
                                @case('partially-paid')
                                    <span class="badge" style="background-color: #007bff;">
                                        {{ ucfirst(__($payment->status)) }}
                                    </span>
                                    @break
                                @case('declined')
                                    <span class="badge" style="background-color: #dc3545;">
                                        {{ ucfirst(__($payment->status)) }}
                                    </span>
                                    @break
                            @endswitch
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <div class="payment-date">
                            {{ $payment->created_at->format('d/m/Y H:i') }}
                        </div>
                        @if($payment->invoice_id && $payment->receiver->id !== \Illuminate\Support\Facades\Auth::user()->id && $payment->status === \App\Model\Transaction::APPROVED_STATUS)
                            <div class="dropdown {{ GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft' }}">
                                <a class="btn btn-sm btn-outline-light dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" style="color: #E93745; border-color: #E93745;">
                                    @include('elements.icon', ['icon' => 'ellipsis-horizontal-outline', 'centered' => false])
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item d-flex align-items-center" href="{{ route('invoices.get', ['id' => $payment->invoice_id]) }}">
                                        @include('elements.icon', ['icon' => 'document-outline', 'centered' => false, 'classes' => 'mr-2']) {{ __('View invoice') }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-center mt-4">
            {{ $payments->onEachSide(1)->links() }}
        </div>
    </div>
    @else
        <div class="p-3">
            <p>{{__('There are no payments on this account.')}}</p>
        </div>
    @endif
</div>







<style>
/* Estilos gerais */
.overview-section {
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Estilos para o modo escuro */
.dark-theme .overview-section {
    background-color: #1c1c1c;
    color: #ffffff;
}

.dark-theme h2 {
    color: #ffffff;
}

.dark-theme .earnings-amount,
.dark-theme .subscribers-count,
.dark-theme .card-title {
    color: #ffffff;
}

/* Estilos para o modo claro */
.light-theme .overview-section {
    background-color: #f8f9fa;
    color: #343a40;
}

.light-theme h2 {
    color: #343a40;
}

.light-theme .earnings-amount,
.light-theme .subscribers-count,
.light-theme .card-title {
    color: #343a40;
}

/* Outros estilos que permanecem iguais */
.custom-select {
    background-color: inherit;
    border-color: inherit;
}

.custom-select:focus, .custom-select:hover {
    border-color: #E93745;
}

.card {
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.payment-card {
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.payment-card:hover {
    transform: translateY(-5px);
}

.sender-name {
    font-weight: 600;
    font-size: 1.1rem;
}

.payment-amount {
    font-size: 1.25rem;
    font-weight: bold;
    color: #E93745;
}

.payment-details, .payment-date {
    font-size: 0.9rem;
    color: #6c757d;
}

.payment-status .badge {
    padding: 5px 10px;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.btn-outline-light {
    border-color: #343a40;
    color: #343a40;
}

.btn-outline-light:hover {
    background-color: #E93745;
    border-color: #E93745;
    color: #fff;
}

.ranking-system {
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: #1f1f1f; /* Um tom mais suave de preto */
    padding: 25px; /* Mais espaçamento para uma aparência mais espaçosa */
    border-radius: 15px; /* Bordas mais arredondadas */
    color: #f0f0f0; /* Um branco mais suave para o texto */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Adiciona uma sombra sutil para destacar o conteúdo */
}

.sales-progress {
    text-align: center;
    margin-bottom: 25px; /* Espaçamento adicional */
}

.progress-bar {
    width: 100%;
    background-color: #444; /* Cor de fundo mais escura para contraste */
    height: 12px; /* Barra ligeiramente mais alta */
    border-radius: 6px; /* Bordas suavemente arredondadas */
    overflow: hidden;
    margin-bottom: 12px;
}

.progress {
    height: 100%;
    background-color: #e93745; /* Mantenha a cor de progresso vermelha */
    transition: width 0.3s ease; /* Animação suave ao carregar */
}

.rank-levels {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 20px;
    width: 100%; /* Garanta que os itens ocupem a largura total */
    padding-top: 20px; /* Adiciona espaço na parte superior */
    border-top: 1px solid #333; /* Linha sutil separando as seções */
}

.rank-item {
    text-align: center;
    flex: 1; /* Cada item ocupa uma fração igual da linha */
    max-width: 80px; /* Limite de largura para manter o alinhamento */
}

.rank-item img {
    width: 60px; /* Tamanho ligeiramente maior para visibilidade */
    height: 60px;
    margin-bottom: 8px; /* Espaço entre a imagem e o texto */
    transition: transform 0.3s ease; /* Animação suave ao passar o mouse */
}

.rank-item img:hover {
    transform: scale(1.1); /* Aumenta ligeiramente o tamanho ao passar o mouse */
}

.rank-item p {
    margin-top: 10px;
    color: #f0f0f0; /* Cor de texto mais suave */
    font-size: 14px; /* Tamanho da fonte ajustado */
    font-weight: 600; /* Texto um pouco mais forte */
}

#currentLevel {
    margin: 0;
    font-size: 1.5rem; /* Ajuste do tamanho da fonte */
    font-weight: 600; /* Um pouco mais de ênfase no peso da fonte */
    text-align: left; /* Alinhamento à esquerda */
    word-wrap: break-word; /* Quebra de linha em palavras longas */
}

@media (max-width: 768px) {
    #currentLevel {
        font-size: 1.2rem; /* Ajuste do tamanho da fonte para telas menores */
        text-align: center; /* Centraliza o texto em dispositivos menores */
    }
}

#earningsAmount {
    color: #E93745; /* Mantém a cor original */
    font-size: 2rem; /* Aumenta o tamanho da fonte para mais destaque */
    font-weight: bold; /* Destaca o texto com um peso maior */
    text-align: right; /* Alinha à direita */
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5); /* Adiciona uma leve sombra ao texto para dar mais profundidade */
}

@media (max-width: 768px) {
    #earningsAmount {
        font-size: 1.5rem; /* Tamanho de fonte ajustado para telas menores */
        text-align: center; /* Centraliza o texto em dispositivos menores */
    }
}


</style>

<script>
    // Script para exibir/ocultar campos de data ao selecionar "Selecionar período..."
    document.getElementById('period').addEventListener('change', function () {
        if (this.value === 'custom') {
            document.getElementById('start-date').classList.remove('d-none');
            document.getElementById('end-date').classList.remove('d-none');
        } else {
            document.getElementById('start-date').classList.add('d-none');
            document.getElementById('end-date').classList.add('d-none');
        }
    });
</script>
