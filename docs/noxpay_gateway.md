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
- **Observação:** Utilize a URL pública `https://closyflix.com/payment/NoxPayStatusUpdate` no campo `webhookUrl` para que as notificações de status cheguem corretamente ao Closyflix.

### `GET /payment/{identifier}`
- **Descrição:** Recupera informações detalhadas de um pagamento existente.
- **Parâmetro de caminho:** `identifier` – `code` interno ou `txid`.
- **Resposta 200:**
  - `method`, `status`, `code`, `txid`, `amount`, `end2end`, `receipt`.

### `GET /payment/webhook/resend/{txid}`
- **Descrição:** Reenvia a notificação de webhook para o pagamento identificado por `txid`.
- **Resposta 200:** texto simples confirmando o reenvio.

## Próximos passos sugeridos
1. Implementar um cliente HTTP que injete automaticamente o cabeçalho `api-key`.
2. Criar serviços para cada endpoint com tratamento de erros e logging.
3. Configurar e validar a URL de webhook (`webhookUrl`) durante a criação do pagamento.
4. Mapear os status retornados (`status`) para o fluxo de negócio interno.

## Checklist de verificação de instalação

1. **Validar a APIKEY:**
   ```bash
   curl -H "api-key: <SUA_APIKEY>" https://api2.noxpay.io/test-auth
   ```
   Confirme que os dados do merchant são retornados com sucesso.
2. **Conferir a conta do merchant:**
   ```bash
   curl -H "api-key: <SUA_APIKEY>" https://api2.noxpay.io/account
   ```
   Verifique se o nome e o saldo foram retornados corretamente.
3. **Criar um pagamento de teste:** envie uma requisição `POST /payment` utilizando um `code` único, o valor desejado e defina `webhookUrl` como `https://closyflix.com/payment/NoxPayStatusUpdate`.
4. **Monitorar o webhook:** assegure-se de que o Closyflix receba o POST no endpoint `payment/NoxPayStatusUpdate` e que o log contenha o payload do NoxPay.
5. **Consultar o pagamento:** utilize `GET /payment/{identifier}` (com o `code` ou `txid` gerado) para validar o status retornado pela API.
6. **Reprocessar notificações, se necessário:** execute `GET /payment/webhook/resend/{txid}` e confirme que o webhook é reenviado e processado corretamente.

