# Relatório Técnico: Mapeamento de Acoplamento com WordPress/WooCommerce

**Plugin:** WooCommerce Cielo Payment Gateway  
**Versão Analisada:** 1.29.0  
**Data:** 2026-03-14  
**Objetivo:** Mapear acoplamento para estratégia de Testes Unitários com PHPUnit e Brain\Monkey

---

## 1. COMUNICAÇÃO HTTP - API CIELO

### 1.1 Funções HTTP Utilizadas

**TODAS as requisições HTTP usam as funções nativas do WordPress:**

- ✅ **`wp_remote_post()`** - Para criar vendas, estornos, autorizações
- ✅ **`wp_remote_get()`** - Para consultar status de pagamento
- ❌ **NUNCA usa:** cURL nativo, Guzzle, file_get_contents

### 1.2 Pontos de Comunicação HTTP - Detalhamento por Arquivo

#### **A) LknWcCieloRequest.php** (257 linhas)
**Classe centralizada para PIX**

| Método | Linha | Tipo HTTP | Endpoint | Propósito |
|--------|-------|-----------|----------|-----------|
| `pix_request()` | 53 | `wp_remote_post` | `{apiUrl}/1/sales/` | Criar transação PIX |
| `payment_request()` | 233 | `wp_remote_get` | `{queryUrl}/1/sales/{paymentId}` | Consultar status PIX |

**Headers Padrão PIX:**
```php
// Linha 47-51
$header = array(
    'Content-Type' => 'application/json',
    'MerchantId' => $options['merchant_id'],
    'MerchantKey' => $options['merchant_key']
);
```

**URLs de API (linhas 9-10):**
```php
private $urls = array(
    'https://apisandbox.cieloecommerce.cielo.com.br',      // Sandbox
    'https://api.cieloecommerce.cielo.com.br/'             // Produção
);
private $queryUrl = array(
    'https://apiquerysandbox.cieloecommerce.cielo.com.br/', // Query Sandbox
    'https://apiquery.cieloecommerce.cielo.com.br/'         // Query Produção
);
```

**Timeout:** 120 segundos (linhas 56, 235)

---

#### **B) LknWCGatewayCieloCredit.php** (1.321 linhas)
**Gateway de Cartão de Crédito**

| Local | Linha | Tipo HTTP | Endpoint | Propósito |
|-------|-------|-----------|----------|-----------|
| `process_payment()` | 834 | `wp_remote_post` | `{apiUrl}/1/sales` | Criar venda com cartão |

**Body da Requisição (linhas 808-827):**
```php
$body = array(
    'MerchantOrderId' => $merchantOrderId,
    'Payment' => array(
        'Type' => 'CreditCard',
        'Amount' => (int) $amountFormated,
        'Installments' => $installments,
        'Capture' => (bool) $capture,           // Captura imediata ou diferida
        'SoftDescriptor' => $description,
        'CreditCard' => array(
            'CardNumber' => $cardNum,
            'ExpirationDate' => $cardExp,
            'SecurityCode' => $cardCvv,
            'SaveCard' => $saveCard,
            'Brand' => $provider,
            'CardOnFile' => array('Usage' => 'First')
        ),
    ),
);
```

**Filtro de Estorno (linha 1151):**
```php
$response = apply_filters(
    'lkn_wc_cielo_credit_refund', 
    $url, $merchantId, $merchantSecret, $order_id, $amount
);
```
> ⚠️ **IMPORTANTE:** O filtro permite que outros plugins modifiquem ou substituam completamente a lógica de estorno

---

#### **C) LknWCGatewayCieloDebit.php** (1.746 linhas)
**Gateway de Cartão de Débito** - Múltiplas chamadas HTTP

| Método | Linha | Tipo HTTP | Endpoint | Propósito |
|--------|-------|-----------|----------|-----------|
| `generate_debit_auth_token()` | 556 | `wp_remote_post` | `{authUrl}/oauth2/token` | Obter token OAuth2 para 3D Secure |
| `process_payment()` - Cenário 1 | 1245 | `wp_remote_post` | `{apiUrl}/1/sales` | Venda sem autenticação 3DS |
| `process_payment()` - Cenário 2 | 1286 | `wp_remote_post` | `{apiUrl}/1/sales` | Venda com autenticação 3DS |
| `process_payment()` - Cenário 3 | 1340 | `wp_remote_post` | `{apiUrl}/1/sales` | Segunda tentativa sem 3DS |
| `process_payment()` - Cenário 4 | 1418 | `wp_remote_post` | `{apiUrl}/1/sales` | Terceira tentativa |

**Autenticação OAuth2 (linhas 538-556):**
```php
$url = ($this->get_option('env') == 'production') 
    ? 'https://authsandbox.braspag.com.br/v2/OAuth2Token'
    : 'https://authsandbox.braspag.com.br/v2/OAuth2Token';

$body = array(
    'grant_type' => 'client_credentials',
    'client_id' => $clientId,
    'client_secret' => $clientSecret
);

$response = wp_remote_post($url, array(
    'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
    'body' => $body,
    'timeout' => 120,
));
```

**3D Secure XID/CAVV/ECI (linhas 1275-1285):**
```php
'ExternalAuthentication' => array(
    'Cavv' => $cavv,
    'Xid' => $xid,
    'Eci' => $eci,
    'Version' => $version,
    'ReferenceID' => $refId
)
```

---

#### **D) LknWCGatewayCieloEndpoint.php** (186 linhas)
**REST API Endpoints**

| Método | Linha | Tipo HTTP | Endpoint | Propósito |
|--------|-------|-----------|----------|-----------|
| `orderCapture()` | 73 | `wp_remote_get` | `{queryUrl}/1/cardBin/{bin}` | Validar BIN do cartão |

**REST Routes Registradas (linhas 14-44):**
```php
// GET /wp-json/lknWCGatewayCielo/checkCard
register_rest_route('lknWCGatewayCielo', '/checkCard', array(
    'methods' => 'GET',
    'callback' => array($this, 'orderCapture'),
    'permission_callback' => '__return_true',
));

// DELETE /wp-json/lknWCGatewayCielo/clearOrderLogs
register_rest_route('lknWCGatewayCielo', '/clearOrderLogs', array(
    'methods' => 'DELETE',
    'callback' => array($this, 'clearOrderLogs'),
    'permission_callback' => '__return_true',
));

// GET /wp-json/lknWCGatewayCielo/getAcessToken
register_rest_route('lknWCGatewayCielo', '/getAcessToken', array(
    'methods' => 'GET',
    'callback' => array($this, 'getAcessToken'),
    'permission_callback' => '__return_true',
));

// GET /wp-json/lknWCGatewayCielo/getCardBrand
register_rest_route('lknWCGatewayCielo', '/getCardBrand', array(
    'methods' => 'GET',
    'callback' => array($this, 'getOfflineBinCard'),
    'permission_callback' => '__return_true',
));
```

> ⚠️ **SEGURANÇA:** `permission_callback => '__return_true'` permite acesso público. Endpoints não têm nonce verification.

---

### 1.3 Tratamento de Erros HTTP

**Padrão em TODOS os arquivos:**
```php
// Linha 59 (LknWcCieloRequest), 849 (Credit), etc.
if (is_wp_error($response)) {
    if ('yes' === $debug) {
        $this->log->log('error', var_export($response->get_error_messages(), true), 
            array('source' => 'woocommerce-cielo-credit'));
    }
    throw new Exception(__('Order payment failed...', 'lkn-wc-gateway-cielo'));
}
```

**Validação de Resposta API:**
```php
// Linha 68-84 (LknWcCieloRequest.php)
$response = json_decode($response['body'], true);

// Erro 422
if (isset($response['Payment']['ReturnCode']) && $response['Payment']['ReturnCode'] === '422') {
    return array('sucess' => false, 'response' => 'Error on merchantResponse integration.');
}

// Credenciais inválidas (códigos 129, 132, 101)
if ($response == null || 
    (is_array($response) && isset($response[0]['Code']) &&
    ($response[0]['Code'] == '129' || $response[0]['Code'] == '132' || $response[0]['Code'] == '101'))) {
    return array('sucess' => false, 'response' => 'Invalid credential(s).');
}
```

---

## 2. MANIPULAÇÃO DE PEDIDOS (WooCommerce)

### 2.1 Métodos da Classe WC_Order Utilizados

**O plugin usa EXCLUSIVAMENTE métodos da classe `WC_Order` - NUNCA usa funções procedurais**

#### **A) Atualização de Status**

| Arquivo | Linha | Método | Uso |
|---------|-------|--------|-----|
| LknWcCieloRequest.php | 175 | `$order->update_status($newStatus)` | Atualizar status após polling PIX |
| LknWcCieloHelper.php | 175 | `$order->update_status($status)` | Helper genérico |

**Mapeamento de Status Cielo → WooCommerce (linhas 192-220 LknWcCieloRequest):**
```php
switch ($payment_status) {
    case 1:  // Autorizada
    case 2:  // Capturada
        return $this->pixCompleteStatus(); // 'processing' ou custom
    case 12: // Pendente
        return 'pending';
    case 3:  // Negada
    case 10: // Cancelada
        return 'cancelled';
    default:
        return 'cancelled';
}
```

**Status Customizado PIX (linhas 180-189):**
```php
public function pixCompleteStatus() {
    $pixOptions = get_option('woocommerce_lkn_wc_cielo_pix_settings');
    $status = $pixOptions['payment_complete_status'];
    
    if ("" == $status) {
        $status = 'processing';
    }
    
    return $status;
}
```

---

#### **B) Transaction ID e Metadata**

| Arquivo | Linha | Método | Uso |
|---------|-------|--------|-----|
| LknWCGatewayCieloCredit.php | 844 | `LknWcCieloHelper::saveTransactionMetadata()` | Salvar todos os dados da transação |
| LknWcCieloRequest.php | 102 | `$order->update_meta_data('lknWcCieloOrderLogs', $orderLogs)` | Logs de depuração |
| LknWcCieloHelper.php | 21 | `$order->get_meta('lknWcCieloOrderLogs')` | Recuperar logs |

**saveTransactionMetadata() - Linha 300+ LknWcCieloHelper.php:**

Salva os seguintes metadados no pedido:
```php
$order->update_meta_data('_lkn_cielo_transaction_id', $tid);
$order->update_meta_data('_lkn_cielo_proof_of_sale', $proofOfSale);
$order->update_meta_data('_lkn_cielo_payment_id', $paymentId);
$order->update_meta_data('_lkn_cielo_authorization_code', $authorizationCode);
$order->update_meta_data('_lkn_cielo_card_brand', $provider);
$order->update_meta_data('_lkn_cielo_installments', $installments);
$order->update_meta_data('_lkn_cielo_installment_amount', $installmentAmount);
$order->update_meta_data('_lkn_cielo_capture_type', $capture ? 'Imediata' : 'Diferida');
$order->update_meta_data('_lkn_cielo_return_code', $returnCodeFormatted);
$order->update_meta_data('_lkn_cielo_gateway_masked', $gatewayMasked);
$order->update_meta_data('_lkn_cielo_merchant_order_id', $merchantOrderId);
$order->update_meta_data('_lkn_cielo_is_recurrent', $isRecurrent);
$order->update_meta_data('_lkn_cielo_request_datetime', $requestDateTime);
$order->update_meta_data('_lkn_cielo_http_status', $httpStatus);
$order->update_meta_data('_lkn_cielo_3ds_xid', $xid);
$order->update_meta_data('_lkn_cielo_3ds_cavv', $cavv);
$order->update_meta_data('_lkn_cielo_3ds_eci', $eci);

// PIX específico
$order->update_meta_data('_wc_cielo_qrcode_payment_id', $pixPaymentId);
```

---

#### **C) Notas do Pedido**

| Arquivo | Linha | Método | Uso |
|---------|-------|--------|-----|
| LknWCGatewayCieloCredit.php | 888-900 | `$order->add_order_note()` | Sucesso no pagamento |
| LknWCGatewayCieloCredit.php | 1158 | `$order->add_order_note()` | Falha no estorno |
| LknWCGatewayCieloCredit.php | 1165 | `$order->add_order_note()` | Sucesso no estorno |
| LknWCGatewayCieloDebit.php | 1577, 1584, 1592 | `$order->add_order_note()` | Estornos débito |

**Exemplo de Nota (linhas 888-905 Credit):**
```php
$order->add_order_note(
    __('Payment completed successfully. Payment id:', 'lkn-wc-gateway-cielo') .
    ' ' . $responseDecoded->Payment->PaymentId . PHP_EOL .
    __('Proof of sale (NSU)', 'lkn-wc-gateway-cielo') .
    ' - ' . $responseDecoded->Payment->ProofOfSale . PHP_EOL .
    'TID ' . $responseDecoded->Payment->Tid . ' - ' . $provider .
    PHP_EOL . $cardName . ' ' . $installmentMessage
);
```

---

#### **D) Transaction ID no WooCommerce**

```php
// Linha ~900+ em todos os gateways
$order->set_transaction_id($responseDecoded->Payment->Tid);
$order->save();
```

---

#### **E) Recuperação de Pedidos**

```php
// Padrão usado em todos os gateways
$order = wc_get_order($order_id);

// Endpoint clearOrderLogs (linha 108 LknWCGatewayCieloEndpoint)
$orders = wc_get_orders(array(
    'limit' => -1,
    'meta_key' => 'lknWcCieloOrderLogs',
    'meta_compare' => 'EXISTS',
));
```

---

### 2.2 Hook de Status Customizado

**Linha 885 LknWCGatewayCieloCredit.php:**
```php
do_action("lkn_wc_cielo_change_order_status", $order, $this, $capture);
```

> 💡 **Para Testes:** Esse hook permite que outros plugins alterem o status final do pedido. Deve ser mockado.

---

## 3. ROTAS E CALLBACKS - WEBHOOKS

### 3.1 Arquitetura de Confirmação de Pagamento

**⚠️ IMPORTANTE:** O plugin **NÃO usa webhooks da Cielo**. Usa **polling via WP-Cron**.

#### **A) Sistema de Polling PIX**

**Registro do Cron (linha 175 LknWCCieloPayment.php):**
```php
$this->loader->add_action(
    'lkn_schedule_check_free_pix_payment_hook', 
    LknWcCieloRequest::class, 
    'check_payment', 
    10, 
    2
);
```

**Agendamento (processo de checkout PIX):**
```php
// Agendar verificação a cada minuto
if (!wp_next_scheduled('lkn_schedule_check_free_pix_payment_hook', array($paymentId, $order_id))) {
    wp_schedule_event(time(), 'every_minute', 'lkn_schedule_check_free_pix_payment_hook', 
        array($paymentId, $order_id));
}
```

**Handler (linhas 154-178 LknWcCieloRequest.php):**
```php
public static function check_payment($paymentId, $order_id): void {
    $instance = new self();
    $order = wc_get_order($order_id);
    
    // Consultar API Cielo
    $response = $instance->payment_request($paymentId);
    $response = wp_remote_retrieve_body($response);
    
    // Agendar auto-limpeza após 2 horas
    if (!wp_next_scheduled('lkn_remove_custom_cron_job_hook', array($paymentId, $order_id))) {
        wp_schedule_single_event(
            time() + (120 * 60), 
            'lkn_remove_custom_cron_job_hook', 
            array($paymentId, $order_id)
        );
    }
    
    // Atualizar status apenas se ainda pendente
    if ($order->get_status() === 'pending') {
        $order->update_status($instance->update_status($response));
    }
}
```

**Auto-limpeza (linhas 246-256 LknWcCieloRequest.php):**
```php
public static function lkn_remove_custom_cron_job($paymentId, $orderId): void {
    $timestamp = wp_next_scheduled('lkn_schedule_check_free_pix_payment_hook', 
        array($paymentId, $orderId));
    if ($timestamp !== false) {
        wp_unschedule_event($timestamp, 'lkn_schedule_check_free_pix_payment_hook', 
            array($paymentId, $orderId));
    }
    // Remove também o próprio hook de limpeza
    $timestamp = wp_next_scheduled('lkn_remove_custom_cron_job_hook', 
        array($paymentId, $orderId));
    if ($timestamp !== false) {
        wp_unschedule_event($timestamp, 'lkn_remove_custom_cron_job_hook', 
            array($paymentId, $orderId));
    }
}
```

**Intervalo Customizado:**
```php
// Deve ser registrado via filtro (provavelmente em outro arquivo do plugin pro)
add_filter('cron_schedules', function($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every Minute')
    );
    return $schedules;
});
```

---

### 3.2 REST API Endpoints

**Registro (linha 196 LknWCCieloPayment.php):**
```php
$this->loader->add_action('rest_api_init', $this->lknWcGatewayCieloEndpoint, 
    'registerOrderCaptureEndPoint');
```

**Namespace:** `lknWCGatewayCielo`

**Endpoints Disponíveis:**

| Rota | Método | Arquivo | Linha | Propósito |
|------|--------|---------|-------|-----------|
| `/checkCard` | GET | LknWCGatewayCieloEndpoint | 14-18 | Validar BIN do cartão |
| `/clearOrderLogs` | DELETE | LknWCGatewayCieloEndpoint | 20-24 | Limpar logs de transações |
| `/getAcessToken` | GET | LknWCGatewayCieloEndpoint | 26-30 | Obter token OAuth2 para débito |
| `/getCardBrand` | GET | LknWCGatewayCieloEndpoint | 32-44 | Detectar bandeira offline (regex) |

**Exemplo de uso frontend:**
```javascript
// JavaScript faz requisição ao endpoint
fetch('/wp-json/lknWCGatewayCielo/checkCard?cardbin=411111', {
    method: 'GET'
})
.then(response => response.json())
.then(data => console.log(data.cardQuery));
```

---

### 3.3 AJAX Endpoints

**Registro (linhas 206-215 LknWCCieloPayment.php):**
```php
$this->loader->add_action('wp_ajax_lkn_update_payment_fees', $this, 'ajax_update_payment_fees');
$this->loader->add_action('wp_ajax_nopriv_lkn_update_payment_fees', $this, 'ajax_update_payment_fees');

$this->loader->add_action('wp_ajax_lkn_update_card_type', $this, 'ajax_update_card_type');
$this->loader->add_action('wp_ajax_nopriv_lkn_update_card_type', $this, 'ajax_update_card_type');

$this->loader->add_action('wp_ajax_lkn_get_recent_cielo_orders', $this, 'ajax_get_recent_cielo_orders');
$this->loader->add_action('wp_ajax_nopriv_lkn_get_recent_cielo_orders', $this, 'ajax_get_recent_cielo_orders');
```

> ⚠️ **SEGURANÇA:** Todos os AJAX endpoints verificam nonce (linha 230 em LknWCCieloPayment)

---

## 4. TRATAMENTO DE EXCEÇÕES E LOGS

### 4.1 Sistema de Logging

**Classe Utilizada:** `WC_Logger` (WordPress/WooCommerce nativo)

**Instanciação em todos os gateways:**
```php
// Linha 60 LknWCGatewayCieloCredit.php
// Linha 88 LknWCGatewayCieloDebit.php
// Linha 70 LknWcCieloPix.php
// Linha 16-18 LknWcCieloRequest.php

if (class_exists('WC_Logger')) {
    $this->log = new WC_Logger();
}
```

---

### 4.2 Níveis de Log Utilizados

| Nível | Método | Uso |
|-------|--------|-----|
| `info` | `$this->log->log('info', $data, array('source' => '...'))` | Informações gerais |
| `notice` | `$this->log->notice($message, array('source' => '...'))` | Avisos não críticos |
| `error` | `$this->log->log('error', $data, array('source' => '...'))` | Erros |
| `error` | `$this->log->error($message, array('error' => '...'))` | Erros com contexto |

---

### 4.3 Pontos de Logging

#### **A) PIX Request (LknWcCieloRequest.php)**

**Linha 114-123:**
```php
if ('yes' == $instance->get_option('debug')) {
    $this->log->log('info', 'pixRequest', array(
        'request' => array(
            'url' => $postUrl . '/1/sales/',
            'current_time' => current_time('mysql'),
            'body' => $body,
            'header' => $header,  // Credenciais já mascaradas
        ),
        'response' => $response  // Dados sensíveis já mascarados
    ));
}
```

**Source:** `'woocommerce-cielo-pix'`

---

#### **B) PIX Status Check (LknWcCieloRequest.php)**

**Linha 171-173:**
```php
if (get_option('woocommerce_lkn_wc_cielo_pix_settings')['debug'] == 'yes') {
    $instance->log->notice($response, array('source' => 'woocommerce-cielo-pix'));
}
```

---

#### **C) Credit Card (LknWCGatewayCieloCredit.php)**

**Erro HTTP - Linha 850-852:**
```php
if (is_wp_error($response)) {
    if ('yes' === $debug) {
        $this->log->log('error', var_export($response->get_error_messages(), true), 
            array('source' => 'woocommerce-cielo-credit'));
    }
}
```

**Erro de Estorno - Linha 1155, 1170:**
```php
if ('yes' === $debug) {
    $this->log->log('error', var_export($response, true), 
        array('source' => 'woocommerce-cielo-credit'));
}
```

---

#### **D) Debit Card (LknWCGatewayCieloDebit.php)**

**Padrão Similar:**
```php
if ('yes' === $debug) {
    $this->log->log('error', var_export($response->get_error_messages(), true), 
        array('source' => 'woocommerce-cielo-debit'));
}
```

---

#### **E) BIN Validation (LknWCGatewayCieloEndpoint.php)**

**Linha 78-80:**
```php
if ('yes' === $debitOption['debug']) {
    $log->log('info', json_encode($response), 
        array('source' => 'woocommerce-cielo-debit-bin'));
}
```

---

### 4.4 Logs Salvos em Order Meta

**LknWcCieloHelper.php - Linhas 10-80:**

**Exibição no Admin (metabox):**
```php
public function showOrderLogs(): void {
    $order = wc_get_order($order_id);
    $orderLogs = $order->get_meta('lknWcCieloOrderLogs');
    
    if ($orderLogs && 'yes' === $options['show_order_logs']) {
        add_meta_box(
            'showOrderLogs',
            'Logs das transações',
            array($this, 'showLogsContent'),
            $screen,
            'advanced',
        );
    }
}
```

**Conteúdo do Metabox (linhas 45-80):**
```php
public function showLogsContent($object): void {
    $order = is_a($object, 'WP_Post') ? wc_get_order($object->ID) : $object;
    $orderLogs = $order->get_meta('lknWcCieloOrderLogs');
    $decodedLogs = json_decode($orderLogs, true);
    
    // Exibe: URL, Headers, Body, Response
    // Todos já mascarados (números de cartão, credenciais)
}
```

---

### 4.5 Mascaramento de Dados Sensíveis

#### **A) Credenciais API**

**LknWcCieloHelper.php - Linhas 189-221:**
```php
public static function maskCredential($credential) {
    $length = strlen($credential);
    
    if ($length <= 6) {
        return str_repeat('*', $length);
    }
    elseif ($length <= 8) {
        $showChars = 3;  // Mostra 3 caracteres de cada lado
    } 
    elseif ($length <= 12) {
        $showChars = 4;  // Mostra 4 caracteres de cada lado
    }
    else {
        $showChars = min(6, floor($length / 3));  // Máximo 6 de cada lado
    }
    
    $start = substr($credential, 0, $showChars);
    $end = substr($credential, -$showChars);
    $middleLength = $length - (2 * $showChars);
    $middle = str_repeat('*', $middleLength);
    
    return $start . $middle . $end;
}
```

**Exemplo:**
- `abc123def456ghi789` → `abc123******ghi789`

---

#### **B) Números de Cartão**

**LknWcCieloHelper.php - Linhas 348-351:**
```php
// Para cartões: 4 primeiros + 6 asteriscos + 4 últimos
$gatewayMasked = !empty($gatewayNum) && strlen($gatewayNum) >= 8 ? 
    substr($gatewayNum, 0, 4) . ' **** **** ' . substr($gatewayNum, -4) : 'N/A';
```

**Exemplo:**
- `4111111111111111` → `4111 **** **** 1111`

---

#### **C) CPF/CNPJ**

**LknWcCieloRequest.php - Linhas 136-152:**
```php
private function maskSensitiveData($string) {
    $length = strlen($string);
    
    if ($length <= 12) {
        return $string;  // Não mascara strings curtas
    }
    
    $startLength = intdiv($length - 8, 2);
    $endLength = $length - $startLength - 8;
    
    $start = substr($string, 0, $startLength);
    $end = substr($string, -$endLength);
    
    return $start . str_repeat('*', 8) . $end;
}
```

**Exemplo:**
- `12345678901234` → `123********234`

---

### 4.6 Localização dos Arquivos de Log

**Path:** `wp-content/uploads/wc-logs/`

**Formato do Nome:**
- `woocommerce-cielo-credit-2026-03-14-{hash}.log`
- `woocommerce-cielo-debit-2026-03-14-{hash}.log`
- `woocommerce-cielo-pix-2026-03-14-{hash}.log`
- `woocommerce-cielo-debit-bin-2026-03-14-{hash}.log`

**Acesso via Admin:** WooCommerce → Status → Logs

---

### 4.7 Exceções Lançadas

**Padrão em todos os gateways:**

```php
// Linha 854-856 LknWCGatewayCieloCredit.php
if (is_wp_error($response)) {
    $message = __('Order payment failed. Please review the gateway settings.', 
        'lkn-wc-gateway-cielo');
    throw new Exception(esc_attr($message));
}
```

**Tipo:** `Exception` nativa do PHP (não usa exceções customizadas)

**Captura:** WooCommerce captura automaticamente e exibe mensagem ao usuário

---

## 5. FLUXOS CRÍTICOS - GERAÇÃO PIX

### 5.1 Sequência Completa

```
1. Usuário seleciona PIX no checkout
   ↓
2. WooCommerce chama LknWcCieloPix::process_payment($order_id)
   ↓
3. process_payment() chama LknWcCieloRequest::pix_request()
   ↓
4. pix_request() faz wp_remote_post() para Cielo API
   Request: {
       MerchantOrderId: string,
       Customer: { Name, Identity, IdentityType },
       Payment: { Type: 'Pix', Amount: int }
   }
   ↓
5. API retorna QR Code (Base64 + String) + PaymentId + Status
   ↓
6. saveTransactionMetadata() salva dados no order meta
   ↓
7. $order->update_meta_data('_wc_cielo_qrcode_payment_id', $paymentId)
   ↓
8. Agenda cron job:
   wp_schedule_event(time(), 'every_minute', 
       'lkn_schedule_check_free_pix_payment_hook', 
       array($paymentId, $order_id))
   ↓
9. Frontend exibe QR Code (template lkn-cielo-pix-template.php)
   ↓
10. Cron executa check_payment() a cada minuto:
    - payment_request() faz wp_remote_get() para consultar status
    - update_status() mapeia código Cielo → status WC
    - $order->update_status() atualiza pedido
   ↓
11. Após 2 horas: lkn_remove_custom_cron_job() limpa crons
```

---

### 5.2 Dependências para Mock

| Função/Classe | Tipo | Propósito |
|---------------|------|-----------|
| `wp_remote_post()` | WP Core | HTTP POST para criar PIX |
| `wp_remote_get()` | WP Core | HTTP GET para consultar status |
| `is_wp_error()` | WP Core | Verificar erro HTTP |
| `wp_json_encode()` | WP Core | Serializar body da requisição |
| `wp_remote_retrieve_body()` | WP Core | Extrair corpo da resposta |
| `current_time('mysql')` | WP Core | Timestamp |
| `wc_get_order()` | WC Core | Obter objeto do pedido |
| `$order->update_status()` | WC_Order | Atualizar status |
| `$order->update_meta_data()` | WC_Order | Salvar metadata |
| `$order->get_status()` | WC_Order | Verificar status atual |
| `$order->save()` | WC_Order | Persistir mudanças |
| `wp_schedule_event()` | WP Cron | Agendar polling |
| `wp_next_scheduled()` | WP Cron | Verificar se já agendado |
| `wp_unschedule_event()` | WP Cron | Cancelar agendamento |
| `wp_schedule_single_event()` | WP Cron | Agendar limpeza única |
| `get_option()` | WP Core | Configurações do plugin |
| `WC_Logger` | WC Core | Sistema de logs |

---

## 6. FLUXOS CRÍTICOS - CARTÃO DE CRÉDITO

### 6.1 Sequência Completa

```
1. Usuário preenche dados do cartão no checkout
   ↓
2. WooCommerce chama LknWCGatewayCieloCredit::process_payment($order_id)
   ↓
3. Validações (linha 653+):
   - validate_card_number()
   - validate_card_expiry()
   - validate_card_cvc()
   ↓
4. Sanitiza dados:
   $cardNum = sanitize_text_field($_POST['lkn_cc_number'])
   $cardExp = sanitize_text_field($_POST['lkn_cc_expiry'])
   $cardCvv = sanitize_text_field($_POST['lkn_cc_cvc'])
   $installments = sanitize_text_field($_POST['lkn_cc_installments'])
   ↓
5. Formata valores:
   $amount = $order->get_total()
   $amountFormated = number_format($amount, 2, '', '')  // Remove decimais
   ↓
6. Monta body da requisição (linhas 808-827):
   {
       MerchantOrderId: string,
       Payment: {
           Type: 'CreditCard',
           Amount: int,
           Installments: int,
           Capture: bool,  // true = captura imediata, false = diferida
           SoftDescriptor: string,
           CreditCard: {
               CardNumber: string,
               ExpirationDate: string (MM/YYYY),
               SecurityCode: string,
               SaveCard: bool,
               Brand: string,  // 'Visa', 'Master', etc.
               CardOnFile: { Usage: 'First' }
           }
       }
   }
   ↓
7. Executa filtro de Zero Auth (linha 829):
   do_action('lkn_wc_cielo_zero_auth', $body, $headers, $this)
   ↓
8. wp_remote_post() para Cielo API (linha 834)
   URL: {apiUrl}/1/sales
   Timeout: 120 segundos
   ↓
9. Response da API:
   {
       Payment: {
           Status: int,  // 1=Autorizada, 2=Capturada, 3=Negada
           PaymentId: string,
           ProofOfSale: string (NSU),
           Tid: string,
           ReturnCode: string,
           ReturnMessage: string
       }
   }
   ↓
10. saveTransactionMetadata() salva tudo no order meta (linha 844)
    ↓
11. Verifica status (linha 883):
    if (Status == 1 || Status == 2) {
        // Pagamento aprovado
        do_action("lkn_wc_cielo_change_order_status", $order, $this, $capture)
        $order->add_order_note('Payment completed...')
        $order->set_transaction_id($tid)
        $order->payment_complete($tid)
        return array('result' => 'success', 'redirect' => $this->get_return_url($order))
    }
    ↓
12. Se negado:
    $order->update_status('failed')
    throw new Exception('Payment was declined')
```

---

### 6.2 Captura Diferida

**Linha 814:**
```php
'Capture' => (bool) $capture,  // false = apenas autoriza, não captura
```

**Capturar depois:**
```php
// Deve ser feito via API externa ou filtro customizado
// Plugin não fornece função nativa de captura manual
```

---

### 6.3 Dependências para Mock

| Função/Classe | Tipo | Propósito |
|---------------|------|-----------|
| `wp_remote_post()` | WP Core | HTTP POST para criar venda |
| `sanitize_text_field()` | WP Core | Sanitizar inputs |
| `wp_unslash()` | WP Core | Remover slashes do $_POST |
| `is_wp_error()` | WP Core | Verificar erro HTTP |
| `wp_json_encode()` | WP Core | Serializar body |
| `number_format()` | PHP | Formatar valor |
| `wc_get_order()` | WC Core | Obter pedido |
| `$order->get_total()` | WC_Order | Total do pedido |
| `$order->update_status()` | WC_Order | Status |
| `$order->add_order_note()` | WC_Order | Adicionar nota |
| `$order->set_transaction_id()` | WC_Order | TID |
| `$order->payment_complete()` | WC_Order | Marcar como pago |
| `$order->save()` | WC_Order | Salvar mudanças |
| `LknWcCieloHelper::saveTransactionMetadata()` | Custom | Salvar dados transação |
| `do_action()` | WP Core | Executar hooks |
| `apply_filters()` | WP Core | Filtros customizados |
| `WC_Logger` | WC Core | Logs |

---

## 7. FLUXOS CRÍTICOS - ESTORNO/CANCELAMENTO

### 7.1 Sequência Completa (Credit)

```
1. Admin acessa pedido no WP Admin
   ↓
2. Clica em "Refund" no metabox do WooCommerce
   ↓
3. Informa valor e motivo
   ↓
4. WooCommerce chama LknWCGatewayCieloCredit::process_refund($order_id, $amount, $reason)
   ↓
5. Recupera dados do pedido (linha 1148):
   $order = wc_get_order($order_id)
   $transactionId = $order->get_transaction_id()  // Tid da venda original
   ↓
6. Executa filtro (linha 1151):
   $response = apply_filters(
       'lkn_wc_cielo_credit_refund',
       $url, $merchantId, $merchantSecret, $order_id, $amount
   )
   
   ⚠️ Se outro plugin hooked nesse filtro, pode substituir TODA lógica
   ↓
7. Se filtro retornou WP_Error (linha 1153):
   if (is_wp_error($response)) {
       $this->log->log('error', ...)
       $order->add_order_note('Order refund failed...')
       return false
   }
   ↓
8. Decodifica resposta (linha 1162):
   $responseDecoded = json_decode($response['body'])
   ↓
9. Verifica status Cielo (linha 1164):
   if (Status == 10 || Status == 11 || Status == 2 || Status == 1) {
       // 10 = Cancelada (total)
       // 11 = Cancelada (parcial)
       // 2 = Capturada (pode estornar)
       // 1 = Autorizada (pode estornar)
       
       $order->add_order_note('Order refunded, payment id: ' . $tid)
       return true  // WooCommerce processa o refund
   }
   ↓
10. Se falhou (linha 1169):
    $this->log->log('error', ...)
    $order->add_order_note('Order refund failed...')
    return false
```

---

### 7.2 Filtro de Estorno Customizado

**⚠️ ATENÇÃO:** A linha 1151 permite que outro plugin substitua COMPLETAMENTE a lógica de estorno:

```php
$response = apply_filters(
    'lkn_wc_cielo_credit_refund',
    $url, $merchantId, $merchantSecret, $order_id, $amount
);
```

**Implementação Padrão (provavelmente em plugin PRO):**
```php
add_filter('lkn_wc_cielo_credit_refund', function($url, $merchantId, $merchantSecret, $order_id, $amount) {
    $order = wc_get_order($order_id);
    $transactionId = $order->get_transaction_id();
    
    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret
        ),
        'body' => wp_json_encode(array('Amount' => (int)($amount * 100))),
        'timeout' => 120
    );
    
    // PUT request para cancelamento
    return wp_remote_post($url . '1/sales/' . $transactionId . '/void?amount=' . (int)($amount * 100), $args);
}, 10, 5);
```

---

### 7.3 PIX não tem Estorno

**LknWcCieloPix.php NÃO implementa `process_refund()`**

PIX é **ONE-WAY** (unidirecional). Cancelamento precisa ser manual via:
1. Devolver dinheiro manualmente ao cliente (fora do sistema)
2. Marcar pedido como cancelado no WooCommerce

---

### 7.4 Dependências para Mock

| Função/Classe | Tipo | Propósito |
|---------------|------|-----------|
| `wc_get_order()` | WC Core | Obter pedido |
| `$order->get_transaction_id()` | WC_Order | TID da venda |
| `apply_filters()` | WP Core | Filtro de estorno customizado |
| `is_wp_error()` | WP Core | Verificar erro |
| `json_decode()` | PHP | Parse da resposta |
| `$order->add_order_note()` | WC_Order | Nota de sucesso/falha |
| `WC_Logger` | WC Core | Log de erros |

---

## 8. HOOKS E FILTROS CUSTOMIZADOS

### 8.1 Actions

| Hook | Arquivo | Linha | Propósito |
|------|---------|-------|-----------|
| `lkn_wc_cielo_change_order_status` | LknWCGatewayCieloCredit.php | 885 | Permite customizar status final do pedido |
| `lkn_wc_cielo_zero_auth` | LknWCGatewayCieloCredit.php | 829 | Modificar body/headers antes de Zero Auth |

**Exemplo de uso:**
```php
add_action('lkn_wc_cielo_change_order_status', function($order, $gateway, $capture) {
    if ($capture) {
        $order->update_status('completed');  // Custom status
    } else {
        $order->update_status('on-hold');
    }
}, 10, 3);
```

---

### 8.2 Filters

| Hook | Arquivo | Linha | Propósito |
|------|---------|-------|-----------|
| `lkn_wc_cielo_credit_refund` | LknWCGatewayCieloCredit.php | 1151 | Substituir lógica de estorno completa |
| `lkn_wc_cielo_debit_refund` | LknWCGatewayCieloDebit.php | 1570 | Substituir lógica de estorno débito |
| `lkn_wc_change_bin_url` | LknWCGatewayCieloEndpoint.php | 62 | Alterar URL de validação BIN |
| `lkn_wc_change_bin_headers` | LknWCGatewayCieloEndpoint.php | 70 | Alterar headers de validação BIN |
| `lkn_wc_check_bin_response` | LknWCGatewayCieloEndpoint.php | 82 | Modificar resposta do BIN check |
| `lkn_wc_cielo_credit_add_support` | LknWCGatewayCieloCredit.php | 74 | Adicionar suporte a features WC (subscriptions, etc.) |
| `lkn_wc_cielo_debit_add_support` | LknWCGatewayCieloDebit.php | 73 | Adicionar suporte a features WC |

**Exemplo de uso:**
```php
// Adicionar suporte a assinaturas
add_filter('lkn_wc_cielo_credit_add_support', function($supports) {
    $supports[] = 'subscriptions';
    $supports[] = 'subscription_cancellation';
    $supports[] = 'subscription_reactivation';
    return $supports;
});
```

---

## 9. ESTRUTURA DE CLASSES - HERANÇA

### 9.1 Todos os Gateways Estendem WC_Payment_Gateway

```php
// Linha 33 LknWCGatewayCieloCredit.php
final class LknWCGatewayCieloCredit extends WC_Payment_Gateway

// Linha 31 LknWCGatewayCieloDebit.php
final class LknWCGatewayCieloDebit extends WC_Payment_Gateway

// Linha 11 LknWcCieloPix.php
final class LknWcCieloPix extends WC_Payment_Gateway

// Linha 20 LknWCGatewayCieloGooglePay.php
final class LknWCGatewayCieloGooglePay extends WC_Payment_Gateway
```

**Métodos Herdados Usados:**
- `$this->get_option($key)` - Recuperar configurações
- `$this->add_notice_once($message, $type)` - Exibir avisos
- `$this->get_return_url($order)` - URL de retorno pós-pagamento
- `$this->init_form_fields()` - Campos de configuração admin
- `$this->init_settings()` - Carregar configurações

---

### 9.2 Classes Finais (Não Extensíveis)

TODAS as classes são `final` - não podem ser estendidas.

**Para customizar:** Use hooks/filtros.

---

## 10. CONSIDERAÇÕES PARA TESTES UNITÁRIOS

### 10.1 Funções WordPress a Mockar

**Core WP:**
```php
// HTTP
wp_remote_post()
wp_remote_get()
is_wp_error()
wp_remote_retrieve_body()
wp_json_encode()

// Sanitização
sanitize_text_field()
wp_unslash()
esc_attr()

// Options
get_option()
update_option()

// Post Meta
update_post_meta()
get_post_meta()

// Cron
wp_schedule_event()
wp_next_scheduled()
wp_unschedule_event()
wp_schedule_single_event()

// Time
current_time()

// Hooks
do_action()
apply_filters()
add_action()
add_filter()

// i18n
__()
_e()
esc_html()
```

---

### 10.2 Funções WooCommerce a Mockar

```php
// Orders
wc_get_order()
wc_get_orders()

// Price
wc_get_price_decimals()

// Subscriptions (se testando)
WC_Subscriptions_Order::order_contains_subscription()
```

---

### 10.3 Classes a Mockar

```php
// WC_Order
->get_total()
->get_status()
->get_transaction_id()
->get_payment_method()
->get_meta()
->update_status()
->update_meta_data()
->add_order_note()
->set_transaction_id()
->payment_complete()
->save()

// WC_Logger
->log()
->notice()
->error()

// WC_Payment_Gateway
->get_option()
->add_notice_once()
->get_return_url()
```

---

### 10.4 Dados de Teste - API Cielo

**Cartões de Teste (Sandbox):**
```
Visa Autorizado:    4111 1111 1111 1111
Master Autorizado:  5555 5555 5555 4444
Elo Autorizado:     6362 9704 1222 7893

CVV: Qualquer 3 dígitos
Validade: Qualquer data futura
```

**Respostas Mockadas:**

```json
// PIX Sucesso
{
  "Payment": {
    "QrCodeBase64Image": "iVBORw0KG...",
    "QrCodeString": "00020101021226...",
    "Status": 12,
    "PaymentId": "12345678-1234-1234-1234-123456789012"
  }
}

// Credit Autorizado
{
  "Payment": {
    "Status": 1,
    "PaymentId": "12345678-1234-1234-1234-123456789012",
    "ProofOfSale": "123456",
    "Tid": "1234567890123456",
    "ReturnCode": "00",
    "ReturnMessage": "Transacao autorizada"
  }
}

// Credit Negado
{
  "Payment": {
    "Status": 3,
    "ReturnCode": "05",
    "ReturnMessage": "Não autorizada"
  }
}

// Credenciais Inválidas
[
  {
    "Code": 129,
    "Message": "MerchantId is required"
  }
]
```

---

### 10.5 Estratégia de Injeção de Dependência

**Problema:** Classes são `final` e constroem dependências no `__construct()`

**Solução 1 - Brain\Monkey:**
```php
use Brain\Monkey\Functions;

// Mock de funções WordPress
Functions\when('wp_remote_post')->justReturn(array(
    'body' => json_encode(array('Payment' => array('Status' => 1)))
));
Functions\when('is_wp_error')->justReturn(false);
```

**Solução 2 - Usar Filtros:**
```php
// Adicionar filtro no código que retorna mock
add_filter('lkn_wc_cielo_credit_refund', function() {
    return array('body' => json_encode(array('Status' => 10)));
});
```

**Solução 3 - Mockery para WC_Order:**
```php
use Mockery;

$orderMock = Mockery::mock('WC_Order');
$orderMock->shouldReceive('get_total')->andReturn(100.00);
$orderMock->shouldReceive('update_status')->with('processing');
```

---

## 11. CHECKLIST DE TESTES

### 11.1 PIX
- [ ] Geração de QR Code
- [ ] Agendamento de cron job
- [ ] Polling de status (cada status Cielo)
- [ ] Auto-limpeza após 2 horas
- [ ] Erro de credenciais inválidas
- [ ] Erro de API offline
- [ ] Mascaramento de CPF/CNPJ
- [ ] Salvamento de metadata

### 11.2 Credit Card
- [ ] Validação de número de cartão
- [ ] Validação de validade
- [ ] Validação de CVV
- [ ] Autorização com captura imediata
- [ ] Autorização com captura diferida
- [ ] Parcelas com juros
- [ ] Negação de pagamento
- [ ] Erro de rede
- [ ] Salvamento de metadata
- [ ] Mascaramento de número de cartão

### 11.3 Refund
- [ ] Estorno total
- [ ] Estorno parcial
- [ ] Estorno de transação já cancelada
- [ ] Erro de rede no estorno
- [ ] Filtro customizado de estorno

### 11.4 Logging
- [ ] Debug mode ON salva logs
- [ ] Debug mode OFF não salva logs
- [ ] Credenciais mascaradas nos logs
- [ ] Order meta logs salvos corretamente
- [ ] Metabox exibido no admin

### 11.5 Hooks
- [ ] `lkn_wc_cielo_change_order_status` executado
- [ ] Filtro de estorno pode substituir lógica
- [ ] Zero Auth hook executado
- [ ] Suporte a features pode ser adicionado

---

## 12. PONTOS DE ATENÇÃO

### 12.1 Segurança

1. ⚠️ **REST Endpoints sem Nonce:**
   - `/checkCard`, `/getCardBrand`, `/getAcessToken` são públicos
   - Validação apenas por `strlen($param) >= 6`
   - Podem ser abusados para DDoS ou descoberta de informações

2. ⚠️ **Credenciais em Logs:**
   - Mascaramento feito, mas se `debug=no`, dados podem vazar em exceções

3. ⚠️ **Filtros Abertos:**
   - `lkn_wc_cielo_credit_refund` permite qualquer plugin estornar pagamentos
   - Sem verificação de `current_user_can('manage_woocommerce')`

---

### 12.2 Performance

1. ⚠️ **Cron Jobs Acumulados:**
   - Se site tiver muitos pedidos PIX, pode ter centenas de crons agendados
   - Verificar se `every_minute` é muito agressivo

2. ⚠️ **Timeout de 120s:**
   - Pode bloquear PHP-FPM se Cielo estiver lenta
   - Considerar timeout menor (30-60s)

---

### 12.3 Compatibilidade

1. ✅ **WooCommerce Blocks:**
   - Suporte completo via `LknWcCieloCreditBlocks`, etc.

2. ✅ **HPOS (High-Performance Order Storage):**
   - Verifica `CustomOrdersTableController` no helper (linha 28)

3. ⚠️ **Subscriptions:**
   - Código menciona mas não está totalmente implementado no free

---

## 13. ARQUIVOS DE CONFIGURAÇÃO

### 13.1 composer.json
```json
{
    "require": {
        "helgesverre/toon": "^1.0"  // Encoder para dados customizados
    }
}
```

### 13.2 package.json
```json
{
    "scripts": {
        "start": "webpack --mode development --watch",
        "build": "webpack --mode production"
    },
    "dependencies": {
        "@wordpress/element": "^5.0.0"
    }
}
```

---

## 14. CONCLUSÃO

### 14.1 Nível de Acoplamento: MÉDIO-ALTO

**Fortemente acoplado a:**
- ✅ WordPress HTTP API (wp_remote_*)
- ✅ WooCommerce Order API (WC_Order)
- ✅ WordPress Cron (wp_schedule_*)
- ✅ WooCommerce Logger (WC_Logger)

**Moderadamente acoplado a:**
- ⚠️ WP REST API (apenas 4 endpoints)
- ⚠️ WP Hooks/Filters (poucos hooks customizados)

**Baixo acoplamento:**
- ✅ Banco de dados (usa abstração WC)
- ✅ Sistema de arquivos (apenas logs WC)

---

### 14.2 Recomendações para Testes

1. **Use Brain\Monkey** para mockar funções WP/WC
2. **Use Mockery** para classes `WC_Order`, `WC_Logger`
3. **Teste os filtros** `lkn_wc_cielo_credit_refund`, etc.
4. **Mocke `wp_remote_*`** com respostas Cielo reais (sandbox)
5. **Teste cron jobs** com `wp_next_scheduled()` mockado
6. **Valide mascaramento** de dados sensíveis em todos os cenários

---

### 14.3 Arquivos Críticos (por ordem de importância)

1. **LknWcCieloRequest.php** - Toda comunicação PIX
2. **LknWCGatewayCieloCredit.php** - Processamento cartão crédito
3. **LknWcCieloHelper.php** - Helpers e metadata
4. **LknWCGatewayCieloDebit.php** - Débito + 3D Secure
5. **LknWCGatewayCieloEndpoint.php** - REST endpoints

---

**Fim do Relatório**

**Próximos Passos:**
1. Revisar este documento com equipe de QA
2. Desenhar casos de teste baseados nos fluxos mapeados
3. Implementar mocks com Brain\Monkey
4. Criar fixtures de resposta da API Cielo
5. Executar testes e ajustar cobertura

---

**Observações Finais:**

- ✅ Plugin segue WordPress Coding Standards
- ✅ Usa apenas funções nativas WP/WC (sem bibliotecas HTTP externas)
- ✅ Logging bem implementado com mascaramento
- ⚠️ Alguns endpoints REST sem proteção adequada
- ⚠️ Filtros muito abertos podem causar problemas de segurança
- ⚠️ Polling a cada minuto pode impactar performance
