@extends('voyager::master')

@section('page_title', __('SuitPay PIX Cash-outs'))

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-dollar"></i> {{ __('SuitPay PIX Cash-outs') }}
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        @if(session('suitpay_cashout_success'))
            <div class="alert alert-success">
                {{ session('suitpay_cashout_success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel panel-bordered">
            <div class="panel-body">
                <p class="text-muted">
                    {{ __('Use esta página para enviar solicitações de cash-out PIX para a SuitPay assim que um saque for aprovado no painel. Antes de processar, confirme que as credenciais da SuitPay estão configuradas em Configurações > Pagamentos.') }}
                </p>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Criado em') }}</th>
                                <th>{{ __('Usuário') }}</th>
                                <th>{{ __('Valor') }}</th>
                                <th>{{ __('Status do saque') }}</th>
                                <th>{{ __('Status SuitPay') }}</th>
                                <th>{{ __('Dados Pix') }}</th>
                                <th>{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($withdrawals as $withdrawal)
                            <tr>
                                <td>#{{ $withdrawal->id }}</td>
                                <td>{{ optional($withdrawal->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($withdrawal->relationLoaded('user') && $withdrawal->user)
                                        <strong>{{ $withdrawal->user->name }}</strong><br>
                                        <small>{{ $withdrawal->user->email }}</small>
                                    @else
                                        {{ __('Usuário removido') }}
                                    @endif
                                </td>
                                <td>{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($withdrawal->amount) }}</td>
                                <td>
                                    <span class="label label-{{ $withdrawal->status === \App\Model\Withdrawal::APPROVED_STATUS ? 'success' : ($withdrawal->status === \App\Model\Withdrawal::REJECTED_STATUS ? 'danger' : 'warning') }}">
                                        {{ __($withdrawal->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($withdrawal->suitpay_cashout_status)
                                        <span class="label label-info">{{ $withdrawal->suitpay_cashout_status }}</span><br>
                                    @else
                                        <span class="text-muted">{{ __('Pendente') }}</span><br>
                                    @endif
                                    @if($withdrawal->suitpay_cashout_id)
                                        <small>{{ __('Transação') }}: {{ $withdrawal->suitpay_cashout_id }}</small><br>
                                    @endif
                                    @if($withdrawal->suitpay_cashout_error)
                                        <small class="text-danger">{{ $withdrawal->suitpay_cashout_error }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div><strong>{{ __('Chave') }}:</strong> {{ $withdrawal->payment_identifier ?? __('não informada') }}</div>
                                    <div><strong>{{ __('Tipo') }}:</strong> {{ $withdrawal->pix_key_type ?? __('não informado') }}</div>
                                    <div><strong>{{ __('Beneficiário') }}:</strong> {{ $withdrawal->pix_beneficiary_name ?? __('não informado') }}</div>
                                    <div><strong>{{ __('Documento') }}:</strong> {{ $withdrawal->pix_document ?? __('não informado') }}</div>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.suitpay.cashouts.process', $withdrawal) }}" class="form-inline">
                                        @csrf
                                        <div class="form-group">
                                            <label class="sr-only" for="payment_identifier_{{ $withdrawal->id }}">{{ __('Chave Pix') }}</label>
                                            <input type="text" class="form-control input-sm" id="payment_identifier_{{ $withdrawal->id }}" name="payment_identifier" value="{{ old('payment_identifier', $withdrawal->payment_identifier) }}" placeholder="{{ __('Chave Pix') }}">
                                        </div>
                                        <div class="form-group mt-1">
                                            <label class="sr-only" for="pix_key_type_{{ $withdrawal->id }}">{{ __('Tipo da chave') }}</label>
                                            <select class="form-control input-sm" id="pix_key_type_{{ $withdrawal->id }}" name="pix_key_type">
                                                @php($options = ['cpf' => 'CPF', 'cnpj' => 'CNPJ', 'email' => 'Email', 'phone' => 'Telefone', 'random' => 'Aleatória', 'paymentcode' => 'QrCode'])
                                                @foreach($options as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('pix_key_type', $withdrawal->pix_key_type) === $value)>{{ __($label) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mt-1">
                                            <label class="sr-only" for="pix_beneficiary_name_{{ $withdrawal->id }}">{{ __('Beneficiário') }}</label>
                                            <input type="text" class="form-control input-sm" id="pix_beneficiary_name_{{ $withdrawal->id }}" name="pix_beneficiary_name" value="{{ old('pix_beneficiary_name', $withdrawal->pix_beneficiary_name) }}" placeholder="{{ __('Nome do Beneficiário') }}">
                                        </div>
                                        <div class="form-group mt-1">
                                            <label class="sr-only" for="pix_document_{{ $withdrawal->id }}">{{ __('Documento') }}</label>
                                            <input type="text" class="form-control input-sm" id="pix_document_{{ $withdrawal->id }}" name="pix_document" value="{{ old('pix_document', $withdrawal->pix_document) }}" placeholder="{{ __('Documento') }}">
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm mt-1" @if($withdrawal->status === \App\Model\Withdrawal::APPROVED_STATUS && $withdrawal->suitpay_cashout_status) disabled @endif>
                                            {{ __('Enviar cash-out') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">{{ __('Nenhum saque com dados de PIX foi encontrado.') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-center">
                    {{ $withdrawals->links() }}
                </div>
            </div>
        </div>
    </div>
@stop
