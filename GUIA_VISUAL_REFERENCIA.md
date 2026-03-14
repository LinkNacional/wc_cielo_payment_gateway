# Guia Visual de Referência - Mapeamento de Acoplamento

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    WooCommerce Cielo Payment Gateway                         │
│                     Mapeamento de Acoplamento v1.29.0                        │
└──────────────────────────────────────────────────────────────────────────────┘
```

## 📑 Índice Rápido

1. [Arquitetura Geral](#1-arquitetura-geral)
2. [Fluxo PIX](#2-fluxo-pix)
3. [Fluxo Cartão de Crédito](#3-fluxo-cartão-de-crédito)
4. [Fluxo de Estorno](#4-fluxo-de-estorno)
5. [Pontos de Integração](#5-pontos-de-integração)
6. [Mapeamento de Mocks](#6-mapeamento-de-mocks)

---

## 1. Arquitetura Geral

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           Plugin Structure                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────┐      ┌─────────────────┐      ┌────────────────┐ │
│  │ LknWCCieloPayment│      │  Payment        │      │   Helper       │ │
│  │   (Orchestrator) │─────▶│  Gateways       │◀─────│   Classes      │ │
│  └─────────────────┘      └─────────────────┘      └────────────────┘ │
│          │                         │                        │           │
│          │                         ▼                        ▼           │
│          │           ┌──────────────────────┐    ┌──────────────────┐ │
│          │           │ LknWcCieloPix        │    │ LknWcCieloHelper │ │
│          │           │ LknWCGatewayCieloCredit    │ LknWcCieloRequest│ │
│          │           │ LknWCGatewayCieloDebit     └──────────────────┘ │
│          │           │ LknWCGatewayCieloGooglePay │                    │
│          │           └──────────────────────┘                          │
│          │                         │                                    │
│          ▼                         ▼                                    │
│  ┌────────────────┐      ┌─────────────────┐                          │
│  │ WP-Cron Hooks  │      │ REST Endpoints  │                          │
│  │ - check_payment│      │ - /checkCard    │                          │
│  │ - cleanup_cron │      │ - /clearLogs    │                          │
│  └────────────────┘      └─────────────────┘                          │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘

                              ▼ Acoplamento ▼

┌─────────────────────────────────────────────────────────────────────────┐
│                      WordPress/WooCommerce Core                          │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────┐ │
│  │ wp_remote_*  │  │  WC_Order    │  │  WP-Cron     │  │ WC_Logger  │ │
│  │ (HTTP API)   │  │  (Orders)    │  │  (Polling)   │  │ (Logging)  │ │
│  └──────────────┘  └──────────────┘  └──────────────┘  └────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘

                              ▼ Comunicação ▼

┌─────────────────────────────────────────────────────────────────────────┐
│                           Cielo API                                      │
├─────────────────────────────────────────────────────────────────────────┤
│  Sandbox: https://apisandbox.cieloecommerce.cielo.com.br               │
│  Production: https://api.cieloecommerce.cielo.com.br                    │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Fluxo PIX

```
┌──────────────────────────────────────────────────────────────────────┐
│                        Fluxo de Pagamento PIX                         │
└──────────────────────────────────────────────────────────────────────┘

Usuário                Plugin                    WP-Cron              Cielo API
  │                      │                          │                     │
  │  1. Seleciona PIX    │                          │                     │
  ├─────────────────────▶│                          │                     │
  │                      │                          │                     │
  │                      │  2. process_payment()    │                     │
  │                      ├──────────────────────────┼────────────────────▶│
  │                      │     wp_remote_post()     │                     │
  │                      │     /1/sales/            │                     │
  │                      │                          │                     │
  │                      │◀─────────────────────────┼─────────────────────┤
  │                      │  3. QR Code + PaymentId  │                     │
  │                      │                          │                     │
  │  4. Exibe QR Code    │                          │                     │
  │◀─────────────────────┤                          │                     │
  │                      │                          │                     │
  │                      │  5. Agenda Cron          │                     │
  │                      ├─────────────────────────▶│                     │
  │                      │  wp_schedule_event()     │                     │
  │                      │  (every_minute)          │                     │
  │                      │                          │                     │
  │                      │                          │  6. Executa a cada  │
  │                      │                          │     minuto          │
  │                      │◀─────────────────────────┤                     │
  │                      │  check_payment()         │                     │
  │                      │                          │                     │
  │                      │  7. Consulta status      │                     │
  │                      ├──────────────────────────┼────────────────────▶│
  │                      │     wp_remote_get()      │                     │
  │                      │     /1/sales/{id}        │                     │
  │                      │                          │                     │
  │                      │◀─────────────────────────┼─────────────────────┤
  │                      │  8. Status atualizado    │                     │
  │                      │                          │                     │
  │                      │  9. update_status()      │                     │
  │                      │     'processing'         │                     │
  │                      │                          │                     │
  │                      │  10. Após 2h: limpa cron │                     │
  │                      ├─────────────────────────▶│                     │
  │                      │  wp_unschedule_event()   │                     │
  │                      │                          │                     │

┌────────────────────────────────────────────────────────────────────────┐
│ Arquivos Envolvidos:                                                   │
│ • LknWcCieloPix.php (linha 653) - process_payment()                   │
│ • LknWcCieloRequest.php (linha 27) - pix_request()                    │
│ • LknWcCieloRequest.php (linha 154) - check_payment()                 │
│ • LknWCCieloPayment.php (linha 175) - Registro do cron hook           │
└────────────────────────────────────────────────────────────────────────┘
```

### Dados Trafegados - PIX

**Request Body:**
```json
{
  "MerchantOrderId": "WC-12345",
  "Customer": {
    "Name": "João Silva",
    "Identity": "123****234",     // CPF mascarado
    "IdentityType": "CPF"
  },
  "Payment": {
    "Type": "Pix",
    "Amount": 10000              // R$ 100,00 (sem decimais)
  }
}
```

**Response:**
```json
{
  "Payment": {
    "QrCodeBase64Image": "iVBORw0KG...",
    "QrCodeString": "00020101021226...",
    "Status": 12,                // 12 = Pendente
    "PaymentId": "uuid-here"
  }
}
```

---

## 3. Fluxo Cartão de Crédito

```
┌──────────────────────────────────────────────────────────────────────┐
│                   Fluxo de Pagamento Cartão de Crédito                │
└──────────────────────────────────────────────────────────────────────┘

Usuário                Plugin                                    Cielo API
  │                      │                                           │
  │  1. Preenche dados   │                                           │
  ├─────────────────────▶│                                           │
  │  • Número do cartão  │                                           │
  │  • Validade          │                                           │
  │  • CVV               │                                           │
  │  • Parcelas          │                                           │
  │                      │                                           │
  │                      │  2. Validações                            │
  │                      │  • validate_card_number()                 │
  │                      │  • validate_card_expiry()                 │
  │                      │  • validate_card_cvc()                    │
  │                      │                                           │
  │                      │  3. Sanitização                           │
  │                      │  • sanitize_text_field()                  │
  │                      │  • Formatação de valores                  │
  │                      │                                           │
  │                      │  4. Monta Requisição                      │
  │                      │  • Body com dados do cartão               │
  │                      │  • Headers com credenciais                │
  │                      │                                           │
  │                      │  5. Hook Zero Auth (opcional)             │
  │                      │  do_action('lkn_wc_cielo_zero_auth')      │
  │                      │                                           │
  │                      │  6. Envia para Cielo                      │
  │                      ├──────────────────────────────────────────▶│
  │                      │     wp_remote_post()                      │
  │                      │     /1/sales                              │
  │                      │                                           │
  │                      │◀──────────────────────────────────────────┤
  │                      │  7. Resposta                              │
  │                      │  • Status (1=Auth, 2=Capt, 3=Neg)         │
  │                      │  • PaymentId, TID, NSU                    │
  │                      │                                           │
  │                      │  8. saveTransactionMetadata()             │
  │                      │  • Salva 17 campos no order meta          │
  │                      │                                           │
  │                      │  9. Verifica Status                       │
  │                      │  if (Status == 1 || Status == 2) {        │
  │                      │    // Aprovado                            │
  │                      │    do_action('lkn_wc_cielo_change_order_status')
  │                      │    $order->payment_complete()             │
  │                      │  } else {                                 │
  │                      │    // Negado                              │
  │                      │    $order->update_status('failed')        │
  │                      │  }                                        │
  │                      │                                           │
  │  10. Retorna sucesso │                                           │
  │◀─────────────────────┤                                           │
  │  ou erro             │                                           │
  │                      │                                           │

┌────────────────────────────────────────────────────────────────────────┐
│ Arquivos Envolvidos:                                                   │
│ • LknWCGatewayCieloCredit.php (linha 653) - process_payment()         │
│ • LknWCGatewayCieloCredit.php (linha 834) - wp_remote_post()          │
│ • LknWcCieloHelper.php (linha 300) - saveTransactionMetadata()        │
└────────────────────────────────────────────────────────────────────────┘
```

### Dados Trafegados - Crédito

**Request Body:**
```json
{
  "MerchantOrderId": "WC-12345",
  "Payment": {
    "Type": "CreditCard",
    "Amount": 10000,
    "Installments": 3,
    "Capture": true,              // ou false para captura diferida
    "SoftDescriptor": "Loja X",
    "CreditCard": {
      "CardNumber": "4111111111111111",
      "ExpirationDate": "12/2027",
      "SecurityCode": "123",
      "SaveCard": false,
      "Brand": "Visa",
      "CardOnFile": { "Usage": "First" }
    }
  }
}
```

**Response (Aprovado):**
```json
{
  "Payment": {
    "Status": 1,                  // 1=Autorizada, 2=Capturada
    "PaymentId": "uuid-here",
    "ProofOfSale": "123456",      // NSU
    "Tid": "1234567890123456",
    "ReturnCode": "00",
    "ReturnMessage": "Transacao autorizada"
  }
}
```

---

## 4. Fluxo de Estorno

```
┌──────────────────────────────────────────────────────────────────────┐
│                        Fluxo de Estorno/Refund                        │
└──────────────────────────────────────────────────────────────────────┘

Admin                  Plugin                                  Cielo API
  │                      │                                         │
  │  1. Clica "Refund"   │                                         │
  ├─────────────────────▶│                                         │
  │  • Informa valor     │                                         │
  │  • Informa motivo    │                                         │
  │                      │                                         │
  │                      │  2. process_refund()                    │
  │                      │  • $order = wc_get_order($order_id)     │
  │                      │  • $tid = $order->get_transaction_id()  │
  │                      │                                         │
  │                      │  3. Filtro Customizado (ATENÇÃO!)       │
  │                      │  $response = apply_filters(             │
  │                      │    'lkn_wc_cielo_credit_refund',        │
  │                      │    $url, $merchantId, $merchantSecret,  │
  │                      │    $order_id, $amount                   │
  │                      │  )                                      │
  │                      │                                         │
  │                      │  ⚠️ Se outro plugin hookar aqui,        │
  │                      │     pode substituir TODA a lógica       │
  │                      │                                         │
  │                      │  4. Envia para Cielo                    │
  │                      ├────────────────────────────────────────▶│
  │                      │     wp_remote_post()                    │
  │                      │     PUT /1/sales/{tid}/void?amount=X    │
  │                      │                                         │
  │                      │◀────────────────────────────────────────┤
  │                      │  5. Resposta                            │
  │                      │  • Status (10=Cancelada, 11=Parcial)    │
  │                      │                                         │
  │                      │  6. Processa Resposta                   │
  │                      │  if (Status == 10 || Status == 11) {    │
  │                      │    $order->add_order_note(              │
  │                      │      'Order refunded, payment id: ' . $tid
  │                      │    )                                    │
  │                      │    return true                          │
  │                      │  } else {                               │
  │                      │    $order->add_order_note('failed')     │
  │                      │    return false                         │
  │                      │  }                                      │
  │                      │                                         │
  │  7. WooCommerce      │                                         │
  │     processa refund  │                                         │
  │◀─────────────────────┤                                         │
  │                      │                                         │

┌────────────────────────────────────────────────────────────────────────┐
│ Arquivos Envolvidos:                                                   │
│ • LknWCGatewayCieloCredit.php (linha 1141) - process_refund()         │
│ • LknWCGatewayCieloCredit.php (linha 1151) - Filtro de estorno        │
│ • LknWCGatewayCieloDebit.php (linha 1560) - process_refund()          │
└────────────────────────────────────────────────────────────────────────┘

⚠️  NOTA IMPORTANTE:
    PIX NÃO tem estorno automatizado. Deve ser feito manualmente.
    LknWcCieloPix.php não implementa process_refund()
```

---

## 5. Pontos de Integração

### 5.1 Comunicação HTTP

```
┌─────────────────────────────────────────────────────────────────────┐
│                    HTTP Communication Layer                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Arquivo                      Linha   Função            Endpoint    │
│  ─────────────────────────────────────────────────────────────────  │
│  LknWcCieloRequest.php          53   wp_remote_post    /1/sales/   │
│  LknWcCieloRequest.php         233   wp_remote_get     /1/sales/{} │
│  LknWCGatewayCieloCredit.php   834   wp_remote_post    /1/sales    │
│  LknWCGatewayCieloDebit.php    556   wp_remote_post    OAuth2Token │
│  LknWCGatewayCieloDebit.php   1245   wp_remote_post    /1/sales    │
│  LknWCGatewayCieloDebit.php   1286   wp_remote_post    /1/sales    │
│  LknWCGatewayCieloDebit.php   1340   wp_remote_post    /1/sales    │
│  LknWCGatewayCieloDebit.php   1418   wp_remote_post    /1/sales    │
│  LknWCGatewayCieloEndpoint.php  73   wp_remote_get     /1/cardBin  │
│                                                                      │
│  ✅ SEMPRE usa wp_remote_* (WordPress HTTP API)                     │
│  ❌ NUNCA usa cURL, Guzzle ou file_get_contents                     │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 5.2 Manipulação de Pedidos

```
┌─────────────────────────────────────────────────────────────────────┐
│                      WC_Order Methods Usage                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Método                       Uso                        Arquivo    │
│  ─────────────────────────────────────────────────────────────────  │
│  $order->get_total()          Valor do pedido           Todos       │
│  $order->get_status()         Status atual              Request.php │
│  $order->get_transaction_id() TID da venda              Credit.php  │
│  $order->get_payment_method() ID do gateway             Helper.php  │
│  $order->get_meta()           Recuperar metadata        Helper.php  │
│  $order->update_status()      Atualizar status          Request.php │
│  $order->update_meta_data()   Salvar metadata           Helper.php  │
│  $order->add_order_note()     Adicionar nota            Todos       │
│  $order->set_transaction_id() Definir TID               Credit.php  │
│  $order->payment_complete()   Marcar como pago          Credit.php  │
│  $order->save()               Persistir mudanças        Todos       │
│                                                                      │
│  ✅ SEMPRE usa métodos WC_Order                                     │
│  ❌ NUNCA usa update_post_meta() ou update_option() direto          │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 5.3 Sistema de Cron Jobs

```
┌─────────────────────────────────────────────────────────────────────┐
│                        WP-Cron Integration                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Hook Name                              Frequência    Handler       │
│  ─────────────────────────────────────────────────────────────────  │
│  lkn_schedule_check_free_pix_payment_hook  Every 1min  check_payment│
│  lkn_remove_custom_cron_job_hook           Once/2h     cleanup      │
│                                                                      │
│  Funções Usadas:                                                    │
│  • wp_schedule_event(time(), 'every_minute', hook, args)            │
│  • wp_next_scheduled(hook, args) - Verifica se agendado             │
│  • wp_unschedule_event(timestamp, hook, args) - Cancela             │
│  • wp_schedule_single_event(time()+7200, hook, args) - Único        │
│                                                                      │
│  ⚠️  ATENÇÃO:                                                        │
│     • 1000 pedidos PIX = 1000 crons executando por minuto           │
│     • Pode impactar performance em lojas com alto volume            │
│     • Considerar implementar webhooks ao invés de polling           │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 5.4 Sistema de Logging

```
┌─────────────────────────────────────────────────────────────────────┐
│                          Logging System                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Logger: WC_Logger (WooCommerce nativo)                              │
│                                                                      │
│  Níveis:                                                             │
│  • log('info', ...)    - Informações gerais                          │
│  • notice(...)         - Avisos não críticos                         │
│  • log('error', ...)   - Erros                                       │
│  • error(...)          - Erros com contexto                          │
│                                                                      │
│  Arquivos de Log:                                                    │
│  wp-content/uploads/wc-logs/woocommerce-cielo-{gateway}-{date}.log  │
│                                                                      │
│  Sources (identificadores):                                          │
│  • 'woocommerce-cielo-pix'        - PIX logs                         │
│  • 'woocommerce-cielo-credit'     - Crédito logs                     │
│  • 'woocommerce-cielo-debit'      - Débito logs                      │
│  • 'woocommerce-cielo-debit-bin'  - Validação BIN                    │
│                                                                      │
│  Order Meta Logs:                                                    │
│  • Meta Key: 'lknWcCieloOrderLogs'                                   │
│  • Formato: JSON com URL, Headers, Body, Response                    │
│  • Exibido em metabox no admin do pedido                             │
│                                                                      │
│  Mascaramento Automático:                                            │
│  • Credenciais API: abc123******ghi789                               │
│  • Números de Cartão: 4111 **** **** 1111                            │
│  • CPF/CNPJ: 123********234                                          │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 6. Mapeamento de Mocks

### 6.1 Funções WordPress a Mockar (Brain\Monkey)

```php
// HTTP
Functions\when('wp_remote_post')->justReturn(['body' => '{"Payment": {...}}']);
Functions\when('wp_remote_get')->justReturn(['body' => '{"Payment": {...}}']);
Functions\when('is_wp_error')->justReturn(false);
Functions\when('wp_remote_retrieve_body')->returnArg(1);
Functions\when('wp_json_encode')->alias('json_encode');

// Sanitização
Functions\when('sanitize_text_field')->returnArg();
Functions\when('wp_unslash')->returnArg();
Functions\when('esc_attr')->returnArg();
Functions\when('esc_html')->returnArg();

// Options
Functions\when('get_option')->justReturn(['merchant_id' => 'test', ...]);
Functions\when('update_option')->justReturn(true);

// Cron
Functions\when('wp_schedule_event')->justReturn(true);
Functions\when('wp_next_scheduled')->justReturn(false);
Functions\when('wp_unschedule_event')->justReturn(true);
Functions\when('wp_schedule_single_event')->justReturn(true);

// Time
Functions\when('current_time')->justReturn('2026-03-14 15:30:00');

// Hooks
Functions\when('do_action')->justReturn(null);
Functions\when('apply_filters')->returnArg(1);
Functions\when('add_action')->justReturn(true);
Functions\when('add_filter')->justReturn(true);

// i18n
Functions\when('__')->returnArg();
Functions\when('_e')->returnArg();
```

### 6.2 Classes WooCommerce a Mockar (Mockery)

```php
use Mockery;

// WC_Order Mock
$orderMock = Mockery::mock('WC_Order');
$orderMock->shouldReceive('get_total')->andReturn(100.00);
$orderMock->shouldReceive('get_status')->andReturn('pending');
$orderMock->shouldReceive('get_transaction_id')->andReturn('TID123456');
$orderMock->shouldReceive('get_payment_method')->andReturn('lkn_cielo_credit');
$orderMock->shouldReceive('get_meta')->with('lknWcCieloOrderLogs')->andReturn('{}');
$orderMock->shouldReceive('update_status')->with('processing')->once();
$orderMock->shouldReceive('update_meta_data')->withAnyArgs()->once();
$orderMock->shouldReceive('add_order_note')->withAnyArgs()->once();
$orderMock->shouldReceive('set_transaction_id')->with('TID123456')->once();
$orderMock->shouldReceive('payment_complete')->with('TID123456')->once();
$orderMock->shouldReceive('save')->once();

// WC_Logger Mock
$loggerMock = Mockery::mock('WC_Logger');
$loggerMock->shouldReceive('log')->withAnyArgs()->zeroOrMoreTimes();
$loggerMock->shouldReceive('notice')->withAnyArgs()->zeroOrMoreTimes();
$loggerMock->shouldReceive('error')->withAnyArgs()->zeroOrMoreTimes();

// wc_get_order() Mock
Functions\when('wc_get_order')->justReturn($orderMock);
```

### 6.3 Respostas Mockadas da API Cielo

```php
// PIX Sucesso
$pixSuccessResponse = [
    'body' => json_encode([
        'Payment' => [
            'QrCodeBase64Image' => 'iVBORw0KGgoAAAANSUhEUgAA...',
            'QrCodeString' => '00020101021226...',
            'Status' => 12,  // Pendente
            'PaymentId' => 'uuid-12345678-1234-1234-1234-123456789012'
        ]
    ])
];

// PIX Status Pago
$pixPaidResponse = [
    'body' => json_encode([
        'Payment' => [
            'Status' => 2,  // Capturada/Paga
            'PaymentId' => 'uuid-12345678-1234-1234-1234-123456789012'
        ]
    ])
];

// Credit Autorizado
$creditApprovedResponse = [
    'body' => json_encode([
        'Payment' => [
            'Status' => 1,  // Autorizada
            'PaymentId' => 'uuid-12345678-1234-1234-1234-123456789012',
            'ProofOfSale' => '123456',
            'Tid' => '1234567890123456',
            'ReturnCode' => '00',
            'ReturnMessage' => 'Transacao autorizada'
        ]
    ])
];

// Credit Negado
$creditDeniedResponse = [
    'body' => json_encode([
        'Payment' => [
            'Status' => 3,  // Negada
            'ReturnCode' => '05',
            'ReturnMessage' => 'Não autorizada'
        ]
    ])
];

// Credenciais Inválidas
$invalidCredentialsResponse = [
    'body' => json_encode([
        [
            'Code' => 129,
            'Message' => 'MerchantId is required'
        ]
    ])
];

// Refund Sucesso
$refundSuccessResponse = [
    'body' => json_encode([
        'Status' => 10,  // Cancelada
        'Tid' => '1234567890123456'
    ])
];
```

---

## 7. Matriz de Testes

```
┌──────────────────────────────────────────────────────────────────────┐
│                      Test Coverage Matrix                             │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  Fluxo            Casos                                     Status   │
│  ──────────────────────────────────────────────────────────────────  │
│  PIX              1. Geração QR Code                        [ ]      │
│                   2. Agendamento cron                       [ ]      │
│                   3. Polling status (cada código Cielo)     [ ]      │
│                   4. Auto-limpeza 2h                        [ ]      │
│                   5. Erro credenciais                       [ ]      │
│                   6. API offline                            [ ]      │
│                   7. Mascaramento CPF                       [ ]      │
│                   8. Metadata salvos                        [ ]      │
│                                                                       │
│  Credit Card      1. Validação número                       [ ]      │
│                   2. Validação validade                     [ ]      │
│                   3. Validação CVV                          [ ]      │
│                   4. Captura imediata                       [ ]      │
│                   5. Captura diferida                       [ ]      │
│                   6. Parcelas com juros                     [ ]      │
│                   7. Pagamento negado                       [ ]      │
│                   8. Erro rede                              [ ]      │
│                   9. Metadata salvos                        [ ]      │
│                   10. Mascaramento cartão                   [ ]      │
│                                                                       │
│  Refund           1. Estorno total                          [ ]      │
│                   2. Estorno parcial                        [ ]      │
│                   3. Transação cancelada                    [ ]      │
│                   4. Erro rede                              [ ]      │
│                   5. Filtro customizado                     [ ]      │
│                                                                       │
│  Logging          1. Debug ON salva                         [ ]      │
│                   2. Debug OFF não salva                    [ ]      │
│                   3. Credenciais mascaradas                 [ ]      │
│                   4. Order meta correto                     [ ]      │
│                   5. Metabox exibido                        [ ]      │
│                                                                       │
│  Hooks            1. Status customizado executado           [ ]      │
│                   2. Filtro estorno substitui lógica        [ ]      │
│                   3. Zero Auth executado                    [ ]      │
│                   4. Suporte features adicionado            [ ]      │
│                                                                       │
│  TOTAL: 32 casos de teste                                            │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 8. Pontos de Atenção de Segurança

```
┌──────────────────────────────────────────────────────────────────────┐
│                        Security Concerns                              │
├──────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ⚠️  1. REST Endpoints Públicos                                       │
│     • Arquivo: LknWCGatewayCieloEndpoint.php                          │
│     • Linhas: 14-44                                                   │
│     • Problema: permission_callback => '__return_true'                │
│     • Risco: Qualquer um pode acessar endpoints sem autenticação      │
│     • Endpoints afetados:                                             │
│       - /checkCard                                                    │
│       - /clearOrderLogs                                               │
│       - /getAcessToken                                                │
│       - /getCardBrand                                                 │
│                                                                       │
│  ⚠️  2. Filtro de Estorno Aberto                                      │
│     • Arquivo: LknWCGatewayCieloCredit.php                            │
│     • Linha: 1151                                                     │
│     • Código: apply_filters('lkn_wc_cielo_credit_refund', ...)        │
│     • Problema: Qualquer plugin pode substituir lógica completa       │
│     • Risco: Estornos fraudulentos se outro plugin malicioso hookar   │
│     • Recomendação: Adicionar verificação current_user_can()          │
│                                                                       │
│  ⚠️  3. Credenciais em Logs (se debug=off)                            │
│     • Problema: Se debug desligado, exceções podem vazar credenciais  │
│     • Risco: Baixo, mas possível se erro não tratado                  │
│     • Recomendação: Sempre mascarar antes de logar                    │
│                                                                       │
│  ✅  4. Boas Práticas Implementadas                                   │
│     • Sanitização de inputs (sanitize_text_field em todos campos)     │
│     • Mascaramento de dados sensíveis (3 métodos diferentes)          │
│     • Uso de WP HTTP API (sem vulnerabilidades de cURL)               │
│     • Validação de cartões antes de enviar à API                      │
│                                                                       │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 9. Referências Rápidas

### Arquivos Principais

| Arquivo | Linhas | Função Principal |
|---------|--------|------------------|
| `LknWcCieloRequest.php` | 257 | Comunicação PIX com API |
| `LknWCGatewayCieloCredit.php` | 1.321 | Gateway crédito |
| `LknWCGatewayCieloDebit.php` | 1.746 | Gateway débito + 3DS |
| `LknWcCieloHelper.php` | 710 | Helpers e metadata |
| `LknWCGatewayCieloEndpoint.php` | 186 | REST endpoints |
| `LknWCCieloPayment.php` | 1.241 | Orchestrador |

### URLs da API Cielo

```
Sandbox:
  - API: https://apisandbox.cieloecommerce.cielo.com.br
  - Query: https://apiquerysandbox.cieloecommerce.cielo.com.br

Production:
  - API: https://api.cieloecommerce.cielo.com.br
  - Query: https://apiquery.cieloecommerce.cielo.com.br
```

### Códigos de Status Cielo

| Código | Significado | Mapeamento WC |
|--------|-------------|---------------|
| 0 | Não finalizada | pending |
| 1 | Autorizada | processing |
| 2 | Capturada/Paga | processing |
| 3 | Negada | failed |
| 10 | Cancelada | cancelled |
| 11 | Cancelada (Parcial) | cancelled |
| 12 | Pendente | pending |
| 13 | Abortada | cancelled |
| 20 | Aguardando | on-hold |

---

## 10. Conclusão

**Nível de Acoplamento:** MÉDIO-ALTO

**Pontos Fortes:**
- ✅ Usa APIs nativas (testável)
- ✅ Código bem estruturado
- ✅ Mascaramento de dados sensíveis
- ✅ Compatível com WC Blocks e HPOS

**Pontos de Melhoria:**
- ⚠️ REST endpoints precisam proteção
- ⚠️ Filtros de estorno muito abertos
- ⚠️ Sistema de polling pode impactar performance
- ⚠️ Considerar webhooks reais

**Documentação Completa:**
- **Mapeamento Técnico**: `MAPEAMENTO_TECNICO_ACOPLAMENTO.md`
- **Resumo Executivo**: `RESUMO_EXECUTIVO.md`
- **Este Guia**: `GUIA_VISUAL_REFERENCIA.md`

---

**Última Atualização:** 2026-03-14  
**Versão do Plugin:** 1.29.0
