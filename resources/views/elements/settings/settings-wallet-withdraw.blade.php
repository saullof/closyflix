<div class="d-flex justify-content-between align-items-center mt-3">
    @if(getSetting('payments.withdrawal_allow_fees') && floatval(getSetting('payments.withdrawal_default_fee_percentage')) > 0)
        <div class="d-flex align-items-center">
            @include('elements.icon',['icon'=>'information-circle-outline','variant'=>'small','centered'=>false,'classes'=>'mr-2'])
            <span class="text-left" id="pending-balance" title="{{__('The payouts are manually and it usually take up to 24 hours for a withdrawal to be processed, we will notify you as soon as your request is processed.')}}">
                {{__('Será aplicado um desconto de R$ :feeAmount por saque realizado.', ['feeAmount'=>floatval(getSetting('payments.withdrawal_default_fee_percentage'))])}}
            </span>

        </div>
    @else
        <h5></h5>
    @endif
    <div class="d-flex align-items-center">
        @include('elements.icon',['icon'=>'information-circle-outline','variant'=>'small','centered'=>false,'classes'=>'mr-2'])
        <span class="text-right" title="{{__('The payouts are manually and it usually take up to 24 hours for a withdrawal to be processed, we will notify you as soon as your request is processed.')}}">
            {{__('Pending balance')}} (<b class="wallet-pending-amount">{{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format(Auth::user()->wallet->pendingBalance, 2, '.', ''))}}</b>)
        </span>
    </div>
</div>
<div class="input-group mb-3 mt-3">
    <div class="input-group-prepend">
        <span class="input-group-text" id="amount-label">@include('elements.icon',['icon'=>'cash-outline','variant'=>'medium'])</span>
    </div>
    <input class="form-control"
           placeholder="{{ \App\Providers\PaymentsServiceProvider::getWithdrawalAmountLimitations() }}"
           aria-label="Username"
           aria-describedby="amount-label"
           id="withdrawal-amount"
           type="number"
           min="{{\App\Providers\PaymentsServiceProvider::getWithdrawalMinimumAmount()}}"
           step="1"
           max="{{\App\Providers\PaymentsServiceProvider::getWithdrawalMaximumAmount()}}">
    <div class="invalid-feedback">{{__('Please enter a valid amount')}}</div>
</div>
<div class="input-group mb-3">
    <div class="flex-row w-100">
        <div class="form-group w-50 pr-2">
            <label for="paymentMethod">{{__('Payment method')}}</label>
            <select class="form-control" id="payment-methods" name="payment-methods">
                @foreach(\App\Providers\PaymentsServiceProvider::getWithdrawalsAllowedPaymentMethods() as $paymentMethod)
                    <option value="{{$paymentMethod}}">{{$paymentMethod}}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex align-items-center" id="saqueinfo">
          @include('elements.icon',['icon'=>'information-circle-outline','variant'=>'small','centered'=>false,'classes'=>'mr-2'])
          <span class="text-right" >
            O saque cairá em até 2 Dias
          </span>
        </div>
        <!-- Alteração no campo de identificação de pagamento para começar oculto -->
        <div class="form-group w-50 d-none" id="payment-identifier-container">
            <label id="payment-identifier-label" for="withdrawal-payment-identifier">{{__("Bank account")}}</label>
            <input class="form-control" type="text" id="withdrawal-payment-identifier" name="payment-identifier">
        </div>
        <div class="form-group w-50 d-none" id="pix-key-type-container">
            <label for="pix-key-type">{{__("Tipo da chave Pix")}}</label>
            <select class="form-control" id="pix-key-type" name="pix-key-type">
                <option value="">{{__('Selecione')}}</option>
                <option value="cpf">{{__('CPF')}}</option>
                <option value="cnpj">{{__('CNPJ')}}</option>
                <option value="email">{{__('Email')}}</option>
                <option value="phone">{{__('Telefone')}}</option>
                <option value="random">{{__('Aleatória')}}</option>
            </select>
        </div>
        <div class="form-group w-50 d-none" id="pix-beneficiary-name-container">
            <label for="pix-beneficiary-name">{{__("Nome do Beneficiado")}}</label>
            <input type="text" class="form-control" id="pix-beneficiary-name" name="pix-beneficiary-name" />
        </div>
        <div class="form-group w-50 d-none" id="pix-document-container">
            <label for="pix-document">{{__("Documento do Beneficiado (CPF/CNPJ)")}}</label>
            <input type="text" class="form-control" id="pix-document" name="pix-document" />
        </div>
    </div>
    <div class="form-group w-100 d-none" id="beneficiary-name-container">
        <label for="beneficiary-name">{{__("Nome do beneficiado")}}</label>
        <input type="text" class="form-control" id="beneficiary-name">
    </div>
    <div class="form-group w-100 d-none" id="bank-details">
        <div class="form-group">
            <label for="nome">Nome do beneficiario</label>
            <input type="text" class="form-control" id="nome">
        </div>
        <!-- Substituição do input de texto por um select para o campo "Banco" -->
        <div class="form-group">
            <label for="bank-select">Banco:</label>
            <select class="form-control" id="bank-select" name="bank">
                <option value="">Selecione o banco</option>
                <option value="001">Banco do Brasil (001)</option>
                <option value="237">Bradesco (237)</option>
                <option value="341">Itaú Unibanco (341)</option>
                <option value="104">Caixa Econômica Federal (104)</option>
                <option value="033">Santander (033)</option>
                <option value="260">Nu Pagamentos S.A (Nubank) (260)</option>
                <option value="290">PagSeguro Internet S.A. (290)</option>
                <option value="212">Banco Original (212)</option>
                <option value="077">Banco Inter (077)</option>
                <option value="745">Banco Citibank (745)</option>
                <option value="399">HSBC Bank Brasil (399)</option>
                <option value="336">C6 Bank (336)</option>
                <option value="756">Banco Cooperativo do Brasil (Sicoob) (756)</option>
                <option value="outro">Outro</option>
            </select>
            <!-- Campo de entrada oculto para o nome do banco personalizado -->
            <input type="text" id="other-bank-input" style="display: none;" placeholder="Digite o nome do banco" />
        </div>
        <label for="account-type">Tipo de Conta</label>
        <select class="form-control" id="account-type">
            <option value="conta_corrente">Conta Corrente</option>
            <option value="conta_poupanca">Conta Poupança</option>
        </select>
        <div class="form-group">
            <label for="agency">Agência</label>
            <input type="text" class="form-control" id="agency">
        </div>
        <div class="form-group">
            <label for="account-number">Número da Conta (com dígito)</label>
            <input type="text" class="form-control" id="account-number">
        </div>
    </div>
    <div class="form-group w-100">
        <textarea type="hidden" class="form-control d-none" id="withdrawal-message" rows="2"></textarea>
        <span class="invalid-feedback" role="alert">
            {{__('Please add your withdrawal notes: EG: Paypal or Bank account.')}}
        </span>
    </div>
</div>

<div class="payment-error error text-danger d-none mt-3">{{__('Add all required info')}}</div>
<button class="btn btn-primary btn-block rounded mr-0 withdrawal-continue-btn" type="submit">{{__('Request withdrawal')}}</button>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const paymentMethodSelect = document.getElementById('payment-methods');
    const withdrawalMessage = document.getElementById('withdrawal-message');
    const bankDetails = document.getElementById('bank-details');
    const paymentIdentifierContainer = document.getElementById('payment-identifier-container');
    const paymentIdentifierLabel = document.getElementById('payment-identifier-label');
    const paymentIdentifierInput = document.getElementById('withdrawal-payment-identifier');
    const submitButton = document.querySelector('.withdrawal-continue-btn'); // Seleciona o botão
    const inputs = bankDetails.querySelectorAll('input');
    const bankSelect = document.getElementById('bank-select');
    const otherBankInput = document.getElementById('other-bank-input');
    const pixKeyTypeContainer = document.getElementById('pix-key-type-container');
    const pixKeyTypeSelect = document.getElementById('pix-key-type');
    const pixBeneficiaryNameContainer = document.getElementById('pix-beneficiary-name-container');
    const pixBeneficiaryNameInput = document.getElementById('pix-beneficiary-name');
    const pixDocumentContainer = document.getElementById('pix-document-container');
    const pixDocumentInput = document.getElementById('pix-document');

    // Inicialmente oculta o botão de submit
    submitButton.style.display = 'none';

    paymentMethodSelect.addEventListener('change', function() {
        const isBankDeposit = this.value === 'Depósito Bancário';
        const isPix = this.value === 'PIX';

        bankDetails.classList.toggle('d-none', !isBankDeposit);
        paymentIdentifierContainer.classList.toggle('d-none', !isPix);
        pixKeyTypeContainer.classList.toggle('d-none', !isPix);
        pixBeneficiaryNameContainer.classList.toggle('d-none', !isPix);
        pixDocumentContainer.classList.toggle('d-none', !isPix);

        paymentIdentifierLabel.textContent = isPix ? "{{__('Sua chave Pix')}}" : '';

        submitButton.style.display = (isBankDeposit || isPix) ? 'block' : 'none';

        inputs.forEach(input => {
            input.required = isBankDeposit;
            if (!isBankDeposit) input.value = '';
        });

        paymentIdentifierInput.required = isPix;
        pixBeneficiaryNameInput.required = isPix;
        pixDocumentInput.required = isPix;

        if (!isPix) {
            pixBeneficiaryNameInput.value = '';
            pixDocumentInput.value = '';
            pixKeyTypeSelect.value = '';
        }

        toggleOtherBankInput();
        updateWithdrawalMessage(); // Atualize a mensagem ao mudar o método
    });


    bankSelect.addEventListener('change', function() {
        toggleOtherBankInput();
        updateWithdrawalMessage(); // Atualiza a mensagem sempre que o banco é alterado
    });

    otherBankInput.addEventListener('input', updateWithdrawalMessage); // Atualiza a mensagem para entradas personalizadas do banco

    attachInputEvents();

    function attachInputEvents() {
        inputs.forEach(input => input.addEventListener('input', updateWithdrawalMessage));
        paymentIdentifierInput.addEventListener('input', updateWithdrawalMessage);
        pixKeyTypeSelect.addEventListener('change', updateWithdrawalMessage);
        pixBeneficiaryNameInput.addEventListener('input', updateWithdrawalMessage);
        pixDocumentInput.addEventListener('input', updateWithdrawalMessage);
    }

    function detachInputEvents() {
        inputs.forEach(input => input.removeEventListener('input', updateWithdrawalMessage));
        paymentIdentifierInput.removeEventListener('input', updateWithdrawalMessage);
        pixKeyTypeSelect.removeEventListener('change', updateWithdrawalMessage);
        pixBeneficiaryNameInput.removeEventListener('input', updateWithdrawalMessage);
        pixDocumentInput.removeEventListener('input', updateWithdrawalMessage);
    }

    function toggleOtherBankInput() {
        otherBankInput.style.display = bankSelect.value === 'outro' ? 'block' : 'none';
        if (bankSelect.value !== 'outro') {
            otherBankInput.value = ''; // Limpa o valor quando "Outro" não é selecionado
        }
    }

    function updateWithdrawalMessage() {
        let message = '';
        if (paymentMethodSelect.value === 'Depósito Bancário') {
            // Mapeamento dos labels com base no id dos inputs e selects
            const labels = {
                "nome": "Nome",
                "account-type": "Tipo de Conta", // Id correto para o <select> de tipos de conta
                "agency": "Agência",
                "account-number": "Número da Conta"
            };

            let bankSelectText = '';
            if (bankSelect.value === 'outro') {
                bankSelectText = otherBankInput.value || 'Banco Personalizado';
            } else {
                bankSelectText = bankSelect.options[bankSelect.selectedIndex].text;
            }

            message += `Banco: ${bankSelectText}; `;

            // Itera sobre todos os inputs e selects, exceto o 'bank-select'
            message += Array.from(document.querySelectorAll('input, select')).filter(input => input.id !== 'bank-select' && input.style.display !== 'none')
                .map(input => {
                    // Verifica se é um select para tratar diferentemente de inputs
                    if (input.tagName.toLowerCase() === 'select') {
                        // Caso especial para selects, usando o texto da opção selecionada
                        const selectedOptionText = input.options[input.selectedIndex].text;
                        return labels[input.id] ? `${labels[input.id]}: ${selectedOptionText}` : '';
                    } else {
                        // Caso padrão para inputs, usando o valor diretamente
                        return labels[input.id] ? `${labels[input.id]}: ${input.value}` : '';
                    }
                })
                .filter(part => part.length > 0) // Remove partes vazias
                .join('; ');
        } else if (paymentMethodSelect.value === 'PIX') {
            const pixKeyTypeText = pixKeyTypeSelect.options[pixKeyTypeSelect.selectedIndex].text;
            const pixKey = paymentIdentifierInput.value;
            const pixBeneficiaryName = pixBeneficiaryNameInput.value;
            const pixDocument = pixDocumentInput.value;
            message = `Nome do Beneficiado: ${pixBeneficiaryName}; Documento: ${pixDocument}; Tipo da chave Pix: ${pixKeyTypeText}; Chave Pix: ${pixKey}`;
        }

        withdrawalMessage.value = message;
    }


});
</script>

<style>
#saqueinfo 
{
  margin-bottom: 15px;
}
</style>


