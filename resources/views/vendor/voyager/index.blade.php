@extends('voyager::master')

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-dashboard"></i> {{__("Platform statistics")}}
    </h1>
@stop

<?php
use Illuminate\Support\Facades\DB;

// Obtém os parâmetros de filtro de data
$dateRange = request('date_range');

$startDate = null;
$endDate = null;

// Verifica se o filtro de data foi enviado e separa as datas
if ($dateRange) {
    $dates = explode(' to ', $dateRange);
    if (count($dates) === 1) {
        // Caso apenas uma data seja fornecida
        $startDate = trim($dates[0]);
        $endDate = trim($dates[0]); // Trata como um único dia
    } elseif (count($dates) === 2) {
        $startDate = trim($dates[0]);
        $endDate = trim($dates[1]);
    }
}

// Ajusta a consulta com base nos filtros e exclui transações do tipo Withdrawal
$topSellersQuery = DB::table('transactions')
    ->select(
        'transactions.recipient_user_id',
        'users.name',
        'users.avatar',
        DB::raw('SUM(CASE WHEN transactions.type NOT IN ("Withdrawal", "Deposit") THEN transactions.amount ELSE 0 END) as total_amount')
    )
    ->join('users', 'users.id', '=', 'transactions.recipient_user_id')
    ->where('transactions.status', 'approved')
    ->groupBy('transactions.recipient_user_id', 'users.name', 'users.avatar')
    ->orderByDesc('total_amount');

// Aplica o filtro de datas
if ($startDate && $endDate) {
    if ($startDate === $endDate) {
        // Filtra por apenas um dia
        $topSellersQuery->whereDate('transactions.created_at', '=', $startDate);
    } else {
        // Inclui o horário final do último dia no intervalo
        $topSellersQuery->whereBetween('transactions.created_at', [
            $startDate,
            (new DateTime($endDate))->modify('+1 day')->format('Y-m-d 00:00:00')
        ]);
    }
}


// Paginação com query string
$topSellers = $topSellersQuery->paginate(10)->appends(request()->query());
?>



@section('content')
    <div class="page-content">
        @include('voyager::alerts')
        @include('voyager::dimmers')
        <div class="analytics-container">
            <!-- Card de alerta com verificações e saques -->
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-info d-flex justify-content-between align-items-center" style="background-color: #D54651; color: #fff; border-radius: 8px; padding: 1rem 1.5rem;">
                        <div>
                            <strong>⚠️ Há novos eventos que exigem sua atenção.</strong>
                            <p style="margin: 0; font-size: 0.9rem;">Por favor, revise-os em:</p>
                            <ul style="list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem;">
                                <li>
                                    Verificações de ID do usuário (<strong>{{ $totalPerfisPendentes }}</strong>)
                                </li>
                                <li>
                                    Retiradas (<strong>{{ $totalSaques }}</strong>)
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtro de período -->
            <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center mb-4">
                <input 
                    type="text" 
                    id="date_range" 
                    name="date_range" 
                    class="form-control flatpickr" 
                    placeholder="Selecione o Periodo" 
                    style="width: 300px; margin-right: 10px;"
                    value="{{ request('date_range') }}"
                >
                <button type="submit" class="btn btn-danger mr-2">Filtrar</button>
                <button type="button" id="clear_date_range" class="btn btn-secondary">{{ __('Clear') }}</button>
            </form>

            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#Rank</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Total Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topSellers as $index => $seller)
                        <tr>
                            <td>#{{ $loop->iteration + ($topSellers->currentPage() - 1) * $topSellers->perPage() }}</td>
                            <td>
                                <a href="#" data-toggle="modal" data-target="#userModal{{ $seller->recipient_user_id }}">
                                    <img src="https://closyflix.nyc3.digitaloceanspaces.com/{{ $seller->avatar }}" class="rounded-circle" alt="Avatar">
                                    {{ $seller->name }}
                                </a>
                            </td>
                            <td>{{ number_format($seller->total_amount, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Paginação -->
            <div class="d-flex justify-content-center">
                {{ $topSellers->links() }}
            </div>
            
            <!-- Modais de usuário -->
            @foreach ($topSellers as $seller)
                <div class="modal fade" id="userModal{{ $seller->recipient_user_id }}" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content modal-red">
                            <div class="modal-header">
                                <h3 class="modal-title" id="userModalLabel">{{ $seller->name }}</h3>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <?php
                                // Aplica os filtros de data às transações do modal
                                $userTransactionsQuery = DB::table('transactions')
                                    ->select('type', DB::raw('SUM(amount) as total_amount'))
                                    ->where('recipient_user_id', $seller->recipient_user_id)
                                    ->where('status', 'approved')
                                    ->groupBy('type');

                                // Filtro de datas
                                if ($startDate && $endDate) {
                                    if ($startDate === $endDate) {
                                        $userTransactionsQuery->whereDate('transactions.created_at', '=', $startDate);
                                    } else {
                                        $userTransactionsQuery->whereBetween('transactions.created_at', [
                                            $startDate,
                                            (new DateTime($endDate))->modify('+1 day')->format('Y-m-d 00:00:00')
                                        ]);
                                    }
                                }

                                // Executa a consulta
                                $userTransactions = $userTransactionsQuery->get();
                                ?>
                                <table class="table table-hover">
                                    <thead class="table-red-header">
                                        <tr>
                                            <th>{{ __('Transaction Type') }}</th>
                                            <th>{{ __('Total Amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($userTransactions as $transaction)
                                            <tr>
                                                <td>{{ ucfirst($transaction->type) }}</td>
                                                <td>R$ {{ number_format($transaction->total_amount, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach





            @if(!checkMysqlndForPDO() || !checkForMysqlND())
                <div class="storage-incorrect-bucket-config tab-additional-info">
                    <div class="alert alert-warning alert-dismissible mb-1">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="info-label"><div class="icon voyager-info-circled"></div><strong>{{__("Warning!")}}</strong></div>
                        <div class=""> {{__("Your PHP's pdo_mysql extension is not using mysqlnd driver. ")}} {{__('This might cause different UI related issues.')}}
                            <div class="mt-05">{{__("Please contact your hosting provider and check if they can enable mysqlnd for pdo_mysql as default driver. Alternatively, you can check if the other PHP versions act the same. ")}}</div>
                        <div class="mt-05">
                            <ul>
                                <li>{{__("Mysqlnd loaded:")}} <strong>{{checkForMysqlND() ? __('True') : __('False')}}</strong></li>
                                <li>{{__("Mysqlnd for PDO:")}} <strong>{{checkMysqlndForPDO()  ? __('True') : __('False')}}</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @include('elements.admin.metrics')

            <div class="row">
                <div class="mb-4 col-md-4">
                    <div class="card shadow rounded p-5">
                        <div class="card-body text-muted font-weight-medium">
                            <a href="https://codecanyon.net/item/justfans-premium-content-creators-saas-platform/35154898" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center info-category-bg">
                                        <div class="icon voyager-world info-category-icon"></div>
                                    </div>
                                    <div class="ml-4 d-flex align-items-center">
                                        <div>
                                            <div class="text-muted font-weight-bolder">{{__("Website")}}</div>
                                            <p class="m-0 text-muted">{{__("Visit the official product page")}}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mb-4 col-md-4">
                    <div class="card shadow rounded p-5">
                        <div class="card-body text-muted font-weight-medium">
                            <a href="https://docs.qdev.tech/justfans/" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center info-category-bg">
                                        <div class="icon voyager-book info-category-icon"></div>
                                    </div>
                                    <div class="ml-4 d-flex align-items-center">
                                        <div>
                                            <div class="text-muted font-weight-bolder">{{__("Documentation")}}</div>
                                            <p class="m-0 text-muted">{{__("Visit the official product docs")}}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mb-4 col-md-4">
                    <div class="card shadow rounded p-5">
                        <div class="card-body text-muted font-weight-medium">
                            <a href="https://codecanyon.net/item/justfans-premium-content-creators-saas-platform/35154898#item-description__changelog" target="_blank">
                                <div class="d-flex align-items-center">
                                    <div class="d-flex align-items-center justify-content-center  info-category-bg">
                                        <div class="icon voyager-file-code info-category-icon"></div>
                                    </div>
                                    <div class="ml-4 d-flex align-items-center">
                                        <div>
                                            <div class="text-muted font-weight-bolder">{{__("Changelog")}}</div>
                                            <p class="m-0 text-muted">{{__("Visit the official product changelog")}}</p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
@stop


<!-- Estilo do Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<!-- Tema Customizado -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

<!-- Script do Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configura o Flatpickr
        flatpickr('.flatpickr', {
            mode: "range", // Modo de intervalo
            enableTime: false, // Desativa seleção de hora
            dateFormat: "Y-m-d", // Formato de data compatível com o backend
            theme: "material_blue", // Tema personalizado
            locale: {
                firstDayOfWeek: 1 // Começa a semana na segunda-feira
            },
        });

        // Botão para limpar o filtro de data
        const clearButton = document.getElementById('clear_date_range');
        const dateInput = document.getElementById('date_range');
        clearButton.addEventListener('click', function() {
            dateInput.value = ''; // Limpa o campo de data
            const form = dateInput.closest('form'); // Encontra o formulário
            form.submit(); // Envia o formulário sem o filtro
        });
    });

</script>



<style>.card.bg-danger {
    border-radius: 8px;
    font-size: 16px;
}
.card .list-group-item {
    border: none;
}

.table img {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    object-fit: cover;
    margin-right: 10px;
}

.table td {
    vertical-align: middle;
}

/* Tema vermelho para o modal */
.modal-red {
    border: 2px solid #ff4d4d;
    background: #fff0f0;
}

.modal-header {
    background: #ff4d4d;
    color: #ffffff;
    border-bottom: 2px solid #cc0000;
}

.modal-header .close {
    color: #ffffff;
    opacity: 0.8;
}

.modal-header .close:hover {
    color: #ffffff;
    opacity: 1;
}

.modal-body {
    color: #660000;
}

/* Estilização da tabela */
.table-red-header {
    background: #ffcccc;
    color: #660000;
    font-weight: bold;
}

.table-hover tbody tr:hover {
    background: #ffe6e6;
}
.page-item.active .page-link {
    background-color: #cc0000 !important; /* Cor de fundo vermelha */
    color: white !important; /* Cor do texto */
    border-color: #cc0000 !important; /* Cor da borda */
}



</style>