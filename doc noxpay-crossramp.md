# Introduction

**Crossramp** is a payment solution that enables direct conversion between fiat currencies (such as the Brazilian Real - BRL) and cryptocurrencies (such as USDT), using PIX as the payment method.

\
Through a simple API integration, it is possible to generate personalized payment links, process KYC when needed, monitor the transaction status in real time, and receive webhook notifications.

\
The system provides a complete journey for the end user — from quotation to confirmation — with a focus on security, transparency, and usability. \
\
The checkout is a product that works as a web-page, that can be either redirected or embedded into your application. The return from the API that generates it (/link) returns its URL.\
\
It **does not** work with any of its parts as a standalone, such as just the PIX payment page or just the quote page. Main reason for that is regulatory compliance, but also the user flow including KYC, payment and settlements where designed as a single experience. Fractioning it may end up in broken or interrupted user flows. \
\
For more information on the User Flows, they are documented extensively on the [Flows](https://noxpay.gitbook.io/docs/flows) page.

# General Implementation

### Integration Flow

1. First you'll need an API Key and access to the Dashboard. Both will be sent to the email registered during onboarding.
2. Then you'll need to set up a Template, for more reference, [read more here](https://noxpay.gitbook.io/docs/templates).
3. After that, you'll need to think which Processes better suits your business model: [onramp](https://noxpay.gitbook.io/docs/flows/onramp), [onramp instant](https://noxpay.gitbook.io/docs/flows/onramp-instant), [offramp](https://noxpay.gitbook.io/docs/flows/offramp) and/or [offramp instant](https://noxpay.gitbook.io/docs/flows/offramp-instant).
4. Now, to generate your first checkout, you'll call the [/link endpoint](https://noxpay.gitbook.io/docs/code-reference#post-link).
5. Once generated, a checkout lasts 10 minutes inbetween quoting and PIX or Crypto deposit. After the deposit, this expiry is void.
6. At every step you'll receive a [webhook](https://noxpay.gitbook.io/docs/webhook) with the relevant information for the step. You can follow it in the Dashboard or via [GET methods](https://noxpay.gitbook.io/docs/code-reference#get-checkout-end2end).
7. And you're all set in the integration department. One single endpoint, multiple possibilities.

### Implementation Flow

#### Option 1: Redirecting to checkout

1. Before creating the checkout, you'll need the values (either in FIAT or in Crypto) that you want to initiate the Checkout with. Example, client wants to onramp 10 BRL to USDC. In this case, also the return URL, which is your application so the client can be redirected back to it upon conclusion.
2. After creating the checkout via API, you'll receive a URL. This is the checkout URL.&#x20;
3. You can simply redirect directly to that URL or add it to a call-to-action on your application so the client can proceed to it.
4. Upon conclusion, client will be redirected back to your return URL.

#### Option 2: Embedding the checkout

1. Before creating the checkout, you'll need the values (either in FIAT or in Crypto) that you want to initiate the Checkout with. Example, client wants to onramp 10 BRL to USDC.
2. After creating the checkout via API, you'll receive a URL. This is the checkout URL. &#x20;
3. You can simply embed this URL or iFrame it on your appliacation as per snippet examples below:

{% tabs %}
{% tab title="Embed example" %}

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Embedded Checkout</title>
    <style>
      body {
        font-family: system-ui, sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 0;
      }
      .checkout-container {
        width: 100%;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
      }
      embed {
        width: 400px;
        height: 600px;
        border: 1px solid #ddd;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
    </style>
  </head>
  <body>
    <div class="checkout-container">
      <embed
        src="https://checkout.noxpay.io/e2e/NOXD1A53EDE037848D4B4CC2A7D208B7E1D"
        type="text/html"
      />
    </div>
  </body>
</html>

```

{% endtab %}

{% tab title="iFrame example" %}

```html
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Embedded Checkout</title>
    <style>
      body {
        font-family: system-ui, sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
      }
      iframe {
        width: 400px;
        height: 600px;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
    </style>
  </head>
  <body>
    <iframe
      src="https://checkout.noxpay.io/e2e/NOXD1A53EDE037848D4B4CC2A7D208B7E1D"
      title="Checkout"
      allowpaymentrequest
      sandbox="allow-scripts allow-forms allow-same-origin"
    ></iframe>
  </body>
</html>

```

{% endtab %}
{% endtabs %}

**Tips for embedding or iFraming:**

1. Test the layouting for all pages, depending on the size of the window it may be best to not add your logo to the checkout in itself.
2. Try in multiple browsers and resolutions to guarantee it is aligned with your application's experience.
3. Don't try to pick parts of our checkout and add a single asset as embed as it will break the flow and generate problems in the experience

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |


# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Onramp

### Process Description

Onramp is the process you select for an Onramp transaction, that is, PIX BRL entry and Cryptocurrency out, without settling immediately. \
\
Using the parameter "onramp" in the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") Method will make the checkout generated by it immediately send Cryptocurrency to the wallet informed in it upon completion.\
\
This process sends the resulting converted cryptocurrency plus any fees you may have charged your client to your available balance at Noxpay. You may withdraw them later at your disclosure on the Dashboard.

### Process Flows - Macro View

Below are all the steps an Onramp process go through up until completion, including KYC related forks.&#x20;

For every step there is a client screen or a webhook sending, they are connected with traced lines to the step.

In the <mark style="background-color:$info;">grey coloured block</mark> there is an example payload to generate an Onramp.\
\
In the <mark style="background-color:orange;">orange coloured blocks</mark> are the steps in which the checkout **MUST** remain available to the client, as there may be still a necessary action on his side. Closing or not displaying those blocks will result in interrupted onramps.

In the <mark style="background-color:yellow;">yellow coloured blocks</mark> are the steps that are mostly informational and towards conclusion, checkout being available at those steps improves user transparency and experience, but it is not mandatory.

{% hint style="warning" %}
It is CRUCIAL that you display the screens to the client in the orange marked blocks, those being:\\

* Quoting
* Pix Deposit screen
* ID validation screens (when appliacable)

Failure to display those screens will result in interrupted flows.
{% endhint %}

{% tabs %}
{% tab title="Full Flow" %}

<figure><img src="https://1890654227-files.gitbook.io/~/files/v0/b/gitbook-x-prod.appspot.com/o/spaces%2FP1PFNQCvMDRzfbFIojy2%2Fuploads%2FsHcL7I9e9jpVUywtctid%2FCrossramp%20Flows2.png?alt=media&#x26;token=5f354b87-b038-4814-ba92-020e094e81c4" alt=""><figcaption></figcaption></figure>
{% endtab %}

{% tab title="Interactable Flow" %}
{% embed url="<https://www.figma.com/board/UIIcUeVx6uUmHLAYKWTfTZ/Crossramp-Flows?node-id=101-2594>" %}
{% endtab %}
{% endtabs %}

### Process Flows - Details and Financial flow Table

In the table below, there is a further description per step, with a column for when there are financial movements. They are all marked by the same order and number as in the Macro Flows above

| Step                                                          | Description                                                                                                                                                                                                                                                                                                    | Financial Info                                                                                                                                                                                                                                                             |
| ------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| <ol><li>Generate checkout via API</li></ol>                   | Step in which you generate the checkout link via the [#post-link](https://noxpay.gitbook.io/docs/code-reference#post-link "mention") method.                                                                                                                                                                   |                                                                                                                                                                                                                                                                            |
| <ol start="2"><li>Quoting</li></ol>                           | Quotation step, here the conversion values and detailed quoting and fee info are displayed to the client.                                                                                                                                                                                                      |                                                                                                                                                                                                                                                                            |
| <ol start="3"><li>PIX Deposit</li></ol>                       | Step in which QR Code and copy-paste available via call-to-action are displayed for client payment. The client is identified by the incoming PIX.                                                                                                                                                              | <p>Upon payment, the BRL balance paid is credited to the <strong>CLIENT'S</strong> account at Nox. <br><br>This step's fullfilment does not imply the values are available to you, nor the fees you may optionally configure.</p>                                          |
| <ol start="4"><li>KYC or Anti-Fraud verification</li></ol>    | In this step we'll quickly verify whether the depositing client has an unresolved KYC or Anti-Fraud Flag, or if the current transaction will flag them. If no flags exist, proceeds to step 5. For more info on KYC and Anti-Fraud, read here. If the Client is a company, proceeds to 4.1, otherwise, to 4.2. | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.1.  Select UBO (Ultimate Beneficiary Owner) to resolve KYC. | Only applies to clients that are companies. In this screen a list of current owners/administrators is displayed. Upon selection, proceeds to 4.2. if cancelled or expired, proceeds to 7.                                                                                                                      | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| 4.2.  Client ID Validation                                    | In this step a link in a QR code and call-to-action are displayed. This link goes to a ID + Liveness verification. In this verification, we expect the document and face of the client or the selected UBO in case of a company. If cancelled, irregular or expired, proceeds to 7.                            | In this step the BRL values re still in the client's account balance.                                                                                                                                                                                                      |
| <ol start="5"><li>Swapping FIAT for Crypto</li></ol>          | In this step we swap the client's BRL balance for the seleted cryptocurrency, in the amounts disclosed on step 1.                                                                                                                                                                                              | In this step the client's balance in BRL is converted to cryptocurrency. It is also transferred to your account at Nox. If there were fees charged by you to the client, those fees also are credited in your balance. The NoxPay's fees are also discounted at this step. |
| <ol start="6"><li>Success</li></ol>                           | In this step, we display a succcess screen, including transaction details and tx Hash for the transaction.                                                                                                                                                                                                     | In this step, the amount in cryptocurrency, including fees you may have charged the client are available in your balance.                                                                                                                                                  |
| <ol start="7"><li>Cancelled</li></ol>                         | In this step, we inform the client we couldn't validate their ID, and their transaction will be refunded and cancelled.                                                                                                                                                                                        | In this step the BRL values are in the client's account balance.                                                                                                                                                                                                           |
| <ol start="8"><li>Processing Refund</li></ol>                 | A refund for the transaction has been queued.                                                                                                                                                                                                                                                                  | In this step, the original amount in BRL is locked in the client's account.                                                                                                                                                                                                |
| <ol start="9"><li>Refund Processed</li></ol>                  | In this step the PIX has been succesfully refunded and transaction cancelled.                                                                                                                                                                                                                                  | In this step the balance is no longer with the client as it has been refunded. No fees are collected by you nor charged by Nox if a process is refunded.                                                                                                                   |

# Webhook

After payment or status update, the system sends a `POST` request to the URL defined in the `webhook` field, with the same structure as the response from `GET /checkout/{end2end}`:

**Webhook Payload:**

```json
{
  "EXACT_deposit_amount": "0.22344200",  // String -  EXACT amount to be deposited to proceed with offramp_instant, only shown on offramp_instant
  "amount_from": 22, // Int - Entry amount in cents
  "amount_to": 122, // Int - Exit amont in cents
  "created_at": "2025-07-01T19:34:25Z", // String - Time of creation in UTC
  "currency_from": "USDT", // String - Entry currency
  "currency_to": "BRL", // String - Exit currency
  "deposit_address": "0x863e6143409464CdB445aC83d0Bde6759f35321b", // String - Deposit address for process offramp_instant
  "deposit_currency": "USDT (AVAX C-Chain)", // String - Deposit currency for process offramp_instant
  "document": "43092838882", // String - Document of PIX receiver/depositor
  "end2end": "NOX0ECDA7AEDAB24EADA7446553CB78F603", // String - Nox unique ID for the transaction, used for querying and support
  "expiration": "2025-07-01T21:44:36Z", // String - Date of expiry of the transaction, in UTC
  "merchant_fees": 0, // Int- Fees charged by the merchant to the client, stay in merchant balance as availability post transaction, in cents, always in the crypto currency, never in the FIAT currency
  "network_fee_tx": 2, // Int - Network fee when applied (only applies on onramp_instant), in cents, always in the crypto currency, never in the FIAT currency
  "nox_fees": 0, // Int - Fees charged by Nox and discounted from the merchant, in cents, always in the crypto currency, never in the FIAT currency
  "quote": 851, // Int - Total quote including fees charged, always in Crypto/FIAT, in cents
  "return_url": "http://localhost:8080", // String - Return url post checkout completion
  "status": "success", // String - Status of the transaction, marked per step of the transaction flow it is at
  "substatus": "DONE", // String - Status of the step in the transaction flow it is at
  "txHash": "0x7f035bce4498787fdfc5423dc8bbbc4e3036034d043cec44d697f9658196b", // String - Hash of the transaction paid (onramp_instant) or received (offramp_instant)
  "wallet":  "0xa97C430abb2E1eaD962eC6fB7e8d4B91E139Dc5C", // String - Wallet amount was sent to (offramp_instant)
  "webhook": "https://webhook.site/123" // String - Webhook address sent to
}
```

#### Transaction Status

#### <kbd>quoting</kbd> - <mark style="background-color:blue;">Quotation step</mark>

#### Substatus:

**INITIAL**: Initiated, not opened by client\
**QUOTE**: Quote shown to client, pending acceptance, expiration count started\
**ERROR**: Error on quote\
**EXPIRED**: Quote expired\
**DONE**: Quote accepted by client

#### <kbd>kyc\_validation</kbd> - <mark style="background-color:blue;">Identity and PIX collection (Offramp and Offramp Instant only)</mark>

#### Substatus:

**INITIAL**: Initiated, not opened by client\
**INFO**: KYC shown to client, pending filling\
**INVALID**: Invalid KYC data\
**DONE**: KYC filled by client and accepted documents

#### <kbd>id\_validation</kbd> - <mark style="background-color:blue;">Additional KYC Required</mark>

#### Substatus:

**INITIAL**: Processing initiated\
**PENDING**: Awaiting Identification\
**CNPJ\_VALIDATION:** Awaiting UBO picking\
**IRREGULAR**: Information is invalid or irregular\
**CANCELLED**: Cancelled by the user, by expiration or by irregularities\
**DONE**: Validation OK

#### <kbd>pix\_deposit</kbd> - <mark style="background-color:blue;">Waiting and then processing PIX-in payment</mark>

#### Substatus:

**INITIAL**: Initiated, not opened by client\
**QRCODE**: QR Code shown to client, pending payment\
**ERROR**: Error on processing fore payment\
**DONE**: Payment received from client and succesfully processed

#### <kbd>pix\_withdrawal</kbd> - <mark style="background-color:blue;">Processing Pix-Out payment</mark>

#### Substatus:

**INITIAL**: Processing initiated\
**WAITING**: Waiting for payment confirmation\
**DIVERGENT**: Payment failed to go through due to divergence inbetween KYC data and PIX key titularity\
**ERROR**: Error on processing payment\
**DONE**: Client received payment

#### <kbd>crypto\_deposit</kbd> - <mark style="background-color:blue;">Waiting and then processing Crypto-in payment</mark>

#### Substatus:

**INITIAL**: Processing initiated\
**WAITING\_DEPOSIT**: Waiting for crypto deposit\
**EXPIRED**: Quote expired before deposit\
**ERROR**: Error on processing payment\
**DONE**: Crypto deposit received

#### <kbd>crypto\_withdrawal</kbd> - <mark style="background-color:blue;">Processing Crypto-out payment</mark>

#### Substatus:

**INITIAL**: Processing initiated\
**WAITING\_TRANSFER**: Processing withdrawal\
**ERROR**: Error on processing withdrawal\
**DONE**: Crypto withdrawal processed

<kbd>**swap\_fiat\_for\_crypto**</kbd>**&#x20;-&#x20;**<mark style="background-color:blue;">**Swapping Client Fiat balance for Crypto balance and then transferring crypto balance from Client to Merchant**</mark>

#### Substatus:

**INITIAL**: Processing initiated\
**ERROR**: Error on processing swap and transfer\
**DONE**: Swap and transfer processed

#### <kbd>swap\_crypto\_for\_fiat</kbd> - <mark style="background-color:blue;">Transferring crypto balance from Merchant to Client, then swapping Client Crypto balance for Fiat balance</mark>

#### Substatus:

**INITIAL**: Processing initiated\
**ERROR**: Error on processing swap and transfer\
**DONE**: Swap and transfer processed

#### <kbd>client\_side\_success</kbd> - <mark style="background-color:blue;">Client has no further action to be taken on checkout and everything performed succesfully</mark>

#### Substatus:

**INITIAL**: Succefully processed






