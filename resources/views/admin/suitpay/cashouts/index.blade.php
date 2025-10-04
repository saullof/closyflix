@extends('voyager::master')

@section('page_title', __('SuitPay PIX Cash-outs'))

@section('page_header')
    <h1 class="page-title">
        <i class="voyager-dollar"></i> {{ __('SuitPay PIX Cash-outs') }}
    </h1>
@stop

@section('content')
    <div class="page-content">
        @include('voyager::alerts')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel panel-bordered">
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Pending withdrawal requests') }}</h3>
            </div>
            <div class="panel-body">
                <p class="text-muted">
                    {{ __('Review the withdrawal details, fill the PIX key information and trigger the cash-out directly through SuitPay.') }}
                </p>

                @forelse ($pendingWithdrawals as $withdrawal)
                    @php
                        $oldContext = old('context_withdrawal_id');
                        $hasOld = $oldContext && intval($oldContext) === $withdrawal->id;
                        $defaultValue = $withdrawal->suitpay_cashout_value ?? ($withdrawal->amount - ($withdrawal->fee ?? 0));
                        $valueInput = $hasOld ? old('value') : number_format($defaultValue, 2, '.', '');
                        $pixKeyValue = $hasOld ? old('pix_key') : $withdrawal->pix_key;
                        $pixTypeValue = $hasOld ? old('pix_key_type') : $withdrawal->pix_key_type;
                        $pixDocumentValue = $hasOld ? old('pix_document') : $withdrawal->pix_document;
                        $userName = optional($withdrawal->user)->name ?? __('Unknown user');
                        $netAmount = $withdrawal->amount - ($withdrawal->fee ?? 0);
                    @endphp
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4 class="panel-title mb-0">
                                #{{ $withdrawal->id }} &mdash; {{ $userName }}
                            </h4>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>{{ __('Amount requested') }}:</strong>
                                    R$ {{ number_format($withdrawal->amount, 2, ',', '.') }}
                                </div>
                                <div class="col-md-4">
                                    <strong>{{ __('Net amount (after fees)') }}:</strong>
                                    R$ {{ number_format(max($netAmount, 0), 2, ',', '.') }}
                                </div>
                                <div class="col-md-4">
                                    <strong>{{ __('Payment method') }}:</strong>
                                    {{ $withdrawal->payment_method ?? __('Not informed') }}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <strong>{{ __('Payment identifier / notes') }}:</strong>
                                    <div>{{ $withdrawal->payment_identifier ?? __('Not provided') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <strong>{{ __('Requester message') }}:</strong>
                                    <div>{{ $withdrawal->message ?? __('No message') }}</div>
                                </div>
                            </div>
                            @if ($withdrawal->suitpay_cashout_status || $withdrawal->suitpay_cashout_message)
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <span class="label label-info">
                                            {{ __('Last SuitPay response') }}:
                                        </span>
                                        <span>
                                            {{ $withdrawal->suitpay_cashout_status ?? '—' }}
                                            @if ($withdrawal->suitpay_cashout_message)
                                                &mdash; {{ $withdrawal->suitpay_cashout_message }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.suitpay.cashouts.execute', $withdrawal) }}" class="mt-3">
                                @csrf
                                <input type="hidden" name="context_withdrawal_id" value="{{ $withdrawal->id }}">
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label class="control-label">{{ __('PIX key') }}</label>
                                        <input type="text" name="pix_key" class="form-control" value="{{ $pixKeyValue }}" required>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="control-label">{{ __('PIX key type') }}</label>
                                        <select name="pix_key_type" class="form-control" required>
                                            <option value="" disabled {{ $pixTypeValue ? '' : 'selected' }}>{{ __('Select') }}</option>
                                            @foreach ($pixKeyTypes as $type => $label)
                                                <option value="{{ $type }}" {{ $pixTypeValue === $type ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="control-label">{{ __('Document validation (optional)') }}</label>
                                        <input type="text" name="pix_document" class="form-control" value="{{ $pixDocumentValue }}" placeholder="000.000.000-00">
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label class="control-label">{{ __('Value (R$)') }}</label>
                                        <input type="number" name="value" class="form-control" step="0.01" min="0.01" value="{{ $valueInput }}" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <i class="voyager-paper-plane"></i> {{ __('Send PIX cash-out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ __('There are no pending withdrawals waiting for SuitPay cash-out.') }}</p>
                @endforelse

                <div class="mt-3">
                    {{ $pendingWithdrawals->links() }}
                </div>
            </div>
        </div>

        <div class="panel panel-bordered">
            <div class="panel-heading">
                <h3 class="panel-title">{{ __('Recent SuitPay cash-outs') }}</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Requested (R$)') }}</th>
                                <th>{{ __('Transferred (R$)') }}</th>
                                <th>{{ __('SuitPay status') }}</th>
                                <th>{{ __('Last update') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentWithdrawals as $cashout)
                                <tr>
                                    <td>#{{ $cashout->id }}</td>
                                    <td>{{ optional($cashout->user)->name ?? __('Unknown user') }}</td>
                                    <td>R$ {{ number_format($cashout->amount, 2, ',', '.') }}</td>
                                    <td>R$ {{ number_format($cashout->suitpay_cashout_value ?? $cashout->amount, 2, ',', '.') }}</td>
                                    <td>{{ $cashout->suitpay_cashout_status ?? '—' }}</td>
                                    <td>
                                        @if ($cashout->suitpay_cashout_confirmed_at)
                                            {{ $cashout->suitpay_cashout_confirmed_at->format('d/m/Y H:i') }}
                                        @else
                                            {{ $cashout->updated_at->format('d/m/Y H:i') }}
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if ($cashout->suitpay_cashout_transaction_id)
                                            <a href="{{ route('admin.suitpay.cashouts.receipt', $cashout) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="voyager-download"></i> {{ __('Receipt') }}
                                            </a>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">{{ __('No cash-outs processed yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
