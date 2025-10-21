# NoxPay Gateway API Reference Summary

Este documento resume os principais endpoints da API NoxPay V2 que serão utilizados na integração do novo gateway de pagamento.

## Autenticação
- Todas as requisições exigem o envio do cabeçalho `api-key` com a chave fornecida pela NoxPay.
- O servidor de testes é `https://api2.noxpay.io`.

## Endpoints

### `GET /test-auth`
- **Descrição:** Valida a `APIKEY` e retorna informações do merchant.
- **Resposta 200:**
  - `id` *(integer)* – identificador interno.
  - `merchantId` *(integer)* – identificador do merchant.
  - `name` *(string)* – nome do merchant.
  - `hash` *(string)* – hash associado à conta.
  - `createdAt` *(string, date-time)* – data de criação.

### `GET /account`
- **Descrição:** Recupera dados da conta vinculada à `APIKEY`.
- **Resposta 200:**
  - `name` *(string)* – nome cadastrado da conta.
  - `balance` *(number)* – saldo disponível.

### `POST /payment`
- **Descrição:** Cria um novo pagamento via PIX (entrada) ou PIXOUT (saída).
- **Headers obrigatórios:** `api-key`.
- **Corpo da requisição:**
  - `method` *(string, enum: `PIX`, `PIXOUT`)* – define se é entrada ou saída.
  - `code` *(string)* – código interno da transação.
  - `amount` *(number)* – valor do pagamento.
  - `webhookUrl` *(string, opcional)* – URL para notificações de status.
  - `clientName` *(string, opcional)* – nome do pagador.
  - `clientDocument` *(string, opcional)* – CPF ou CNPJ do pagador.
  - `type` *(string, enum: `PIX_KEY`, `BANK_ACCOUNT`, opcional para PIXOUT)* – modo de identificação do destinatário.
  - `pixkey` *(string, opcional para PIXOUT)* – chave PIX do recebedor.
  - `bankAccount` *(object, opcional para PIXOUT)* – dados bancários quando o pagamento é por conta bancária.
- **Resposta 201:**
  - `method`, `code`, `amount`, `url`, `qrCode`, `qrCodeText`, `txid`, `status`.

### `GET /payment/{identifier}`
- **Descrição:** Recupera informações detalhadas de um pagamento existente.
- **Parâmetro de caminho:** `identifier` – `code` interno ou `txid`.
- **Resposta 200:**
  - `method`, `status`, `code`, `txid`, `amount`, `end2end`, `receipt`.

### `GET /payment/webhook/resend/{txid}`
- **Descrição:** Reenvia a notificação de webhook para o pagamento identificado por `txid`.
- **Resposta 200:** texto simples confirmando o reenvio.

## Webhooks
- Configure a URL de webhook apontando para `https://{seu_dominio}/payment/noxpay/status`.
- Quando o webhook estiver ativo, informe um segredo na área de configurações da NoxPay e envie-o nos headers `X-Webhook-Secret` (ou `X-Noxpay-Webhook-Secret`). Ele será validado pelo painel administrativo antes de processar o payload.

## Próximos passos sugeridos
1. Implementar um cliente HTTP que injete automaticamente o cabeçalho `api-key`.
2. Criar serviços para cada endpoint com tratamento de erros e logging.
3. Configurar e validar a URL de webhook (`webhookUrl`) durante a criação do pagamento.
4. Mapear os status retornados (`status`) para o fluxo de negócio interno.

