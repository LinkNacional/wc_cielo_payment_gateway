# 📦 Guia de Fixtures da API Cielo

## Visão Geral

Este documento lista todos os fixtures disponíveis para testes com a API Cielo Sandbox. Todos os fixtures estão em `tests/Fixtures/CieloApiFixtures.php`.

---

## 🎯 Como Usar

### Uso Básico

```php
use Lkn\WCCieloPaymentGateway\Tests\Fixtures\CieloApiFixtures;

// Obter fixture específico
$fixture = CieloApiFixtures::pixCreated();

// Ou usar o helper get()
$fixture = CieloApiFixtures::get('pix-created');

// Listar todos os fixtures disponíveis
$allFixtures = CieloApiFixtures::getAvailableFixtures();
```

### Estrutura dos Fixtures

Cada fixture retorna um array com:
```php
[
    'body' => json_encode([...]), // Response body da API
    'response' => ['code' => 201]  // HTTP status code
]
```

Alguns fixtures retornam `WP_Error` para simular erros de rede.

---

## 📋 Fixtures Disponíveis

### PIX (5 fixtures)

| Método | Status | Cenário |
|--------|--------|---------|
| `pixCreated()` | 12 | PIX criado, pendente de pagamento |
| `pixPaid()` | 2 | PIX confirmado/pago |
| `pixExpired()` | 3 | PIX expirado |
| `pixCancelled()` | 10 | PIX cancelado pelo merchant |

**Exemplo:**
```php
$response = CieloApiFixtures::pixCreated();
$payment = json_decode($response['body'], true);
// $payment['Payment']['Status'] === 12
// $payment['Payment']['QrCodeString'] !== null
```

---

### Credit Card (8 fixtures)

| Método | Status | ReturnCode | Cenário |
|--------|--------|------------|---------|
| `creditAuthorized()` | 1 | 4 | Autorizado (sem captura) |
| `creditPaid()` | 2 | 4 | Autorizado e capturado |
| `creditDeniedInsufficientFunds()` | 3 | 51 | Negado - saldo insuficiente |
| `creditDeniedExpired()` | 3 | 57 | Negado - cartão expirado |
| `creditDeniedBlocked()` | 3 | 78 | Negado - cartão bloqueado |
| `creditDeniedGeneric()` | 3 | 05 | Negado - não autorizado |
| `creditTimeout()` | 3 | 99 | Timeout |

**Exemplo:**
```php
$response = CieloApiFixtures::creditPaid();
$payment = json_decode($response['body'], true);
// $payment['Payment']['Status'] === 2
// $payment['Payment']['ProofOfSale'] !== null
// $payment['Payment']['Tid'] !== null
```

---

### Debit Card (1 fixture)

| Método | Status | Cenário |
|--------|--------|---------|
| `debitAuthenticationRedirect()` | 0 | Redirecionamento para autenticação |

**Exemplo:**
```php
$response = CieloApiFixtures::debitAuthenticationRedirect();
$payment = json_decode($response['body'], true);
// $payment['Payment']['AuthenticationUrl'] !== null
```

---

### Refund (3 fixtures)

| Método | Status | Cenário |
|--------|--------|---------|
| `refundFullSuccess()` | 10 | Estorno total bem-sucedido |
| `refundPartialSuccess()` | 11 | Estorno parcial bem-sucedido |
| `refundAlreadyCancelled()` | 10 | Erro - transação já cancelada |

**Exemplo:**
```php
$response = CieloApiFixtures::refundFullSuccess();
$refund = json_decode($response['body'], true);
// $refund['Status'] === 10
// $refund['ReasonCode'] === 0
```

---

### Capture (1 fixture)

| Método | Status | Cenário |
|--------|--------|---------|
| `captureSuccess()` | 2 | Captura diferida bem-sucedida |

**Exemplo:**
```php
$response = CieloApiFixtures::captureSuccess();
$capture = json_decode($response['body'], true);
// $capture['Status'] === 2
```

---

### Zero Auth (2 fixtures)

| Método | ReturnCode | Cenário |
|--------|------------|---------|
| `zeroAuthValid()` | 00 | Cartão válido |
| `zeroAuthInvalid()` | 57 | Cartão inválido/expirado |

**Exemplo:**
```php
$response = CieloApiFixtures::zeroAuthValid();
$result = json_decode($response['body'], true);
// $result['Valid'] === true
```

---

### Erros da API (7 fixtures)

| Método | HTTP Code | Cielo Code | Cenário |
|--------|-----------|------------|---------|
| `errorInvalidCredentials()` | 401 | 129 | MerchantId não fornecido |
| `errorInvalidMerchant()` | 401 | 132 | MerchantId inválido |
| `errorInvalidRequest()` | 400 | 101 | Requisição inválida |
| `errorPaymentNotFound()` | 404 | 404 | Pagamento não encontrado |
| `errorInternalServer()` | 500 | 500 | Erro interno do servidor |
| `errorServiceUnavailable()` | 503 | 503 | Serviço indisponível |
| `errorRateLimitExceeded()` | 429 | 429 | Limite de requisições excedido |

**Exemplo:**
```php
$response = CieloApiFixtures::errorInvalidCredentials();
$error = json_decode($response['body'], true);
// $error[0]['Code'] === 129
// $response['response']['code'] === 401
```

---

### Erros de Rede (3 fixtures - WP_Error)

| Método | Cenário |
|--------|---------|
| `errorNetworkTimeout()` | Timeout após 120s |
| `errorConnectionRefused()` | Conexão recusada |
| `errorSslError()` | Erro de certificado SSL |

**Exemplo:**
```php
$error = CieloApiFixtures::errorNetworkTimeout();
// $error instanceof WP_Error
// $error->get_error_code() === 'http_request_failed'
```

---

## 🎴 Cartões de Teste

Constante: `CieloApiFixtures::TEST_CARDS`

| Bandeira | Tipo | Número |
|----------|------|--------|
| Visa | Aprovado | 4551870000000183 |
| Visa | Negado | 4000000000000010 |
| Mastercard | Aprovado | 5555666677778884 |
| Mastercard | Negado | 5555666677778883 |
| Amex | Aprovado | 376449047333005 |
| Elo | Aprovado | 6362970000457013 |
| Diners | Aprovado | 36490102462661 |
| Hipercard | Aprovado | 6062825624254001 |

**Uso:**
```php
$card = CieloApiFixtures::TEST_CARDS['visa_approved'];
// $card === '4551870000000183'
```

---

## 👤 Cliente de Teste

Constante: `CieloApiFixtures::TEST_CUSTOMER`

```php
[
    'name' => 'João da Silva',
    'cpf' => '12345678900',
    'email' => 'test@cielo.com.br',
    'phone' => '11987654321',
]
```

**Uso:**
```php
$customer = CieloApiFixtures::TEST_CUSTOMER;
// $customer['name'] === 'João da Silva'
```

---

## 📊 Status de Pagamento Cielo

| Status | Descrição |
|--------|-----------|
| 0 | NotFinished (não finalizado) |
| 1 | Authorized (autorizado) |
| 2 | PaymentConfirmed (pago/capturado) |
| 3 | Denied (negado) |
| 10 | Voided (cancelado) |
| 11 | Refunded (estornado parcial) |
| 12 | Pending (pendente - PIX) |
| 13 | Aborted (abortado) |

---

## 🔢 ReturnCodes Importantes

| Code | Mensagem |
|------|----------|
| 0 | Successful |
| 4 | Operation Successful |
| 05 | Not Authorized |
| 51 | Insufficient Funds |
| 57 | Card Expired |
| 78 | Card Blocked |
| 99 | Time Out |
| 129 | MerchantId is required |
| 132 | MerchantId invalid |

---

## 📖 Exemplos Completos

### Teste de PIX

```php
use Lkn\WCCieloPaymentGateway\Tests\Fixtures\CieloApiFixtures;
use Brain\Monkey\Functions;

public function test_pix_qr_code_generation()
{
    // Arrange
    $fixture = CieloApiFixtures::pixCreated();
    
    Functions\when('wp_remote_post')->justReturn($fixture);
    Functions\when('wp_remote_retrieve_body')->justReturn($fixture['body']);
    Functions\when('wp_remote_retrieve_response_code')->justReturn(201);
    
    // Act
    $result = $gateway->pix_request($orderId);
    
    // Assert
    $this->assertArrayHasKey('qr_code', $result);
    $this->assertEquals(12, $result['status']);
}
```

### Teste de Credit Card

```php
public function test_credit_card_authorization()
{
    // Arrange
    $fixture = CieloApiFixtures::creditAuthorized();
    
    Functions\when('wp_remote_post')->justReturn($fixture);
    
    // Act
    $result = $gateway->process_payment($orderId);
    
    // Assert
    $payment = json_decode($fixture['body'], true);
    $this->assertEquals(1, $payment['Payment']['Status']);
    $this->assertEquals('4', $payment['Payment']['ReturnCode']);
}
```

### Teste de Erro

```php
public function test_invalid_credentials_error()
{
    // Arrange
    $fixture = CieloApiFixtures::errorInvalidCredentials();
    
    Functions\when('wp_remote_post')->justReturn($fixture);
    
    // Act
    $result = $gateway->process_payment($orderId);
    
    // Assert
    $this->assertTrue(is_wp_error($result));
}
```

---

## 🔗 Referências

- [Documentação Oficial Cielo API 3.0](https://developercielo.github.io/manual/cielo-ecommerce)
- [Sandbox Cielo](https://cadastrosandbox.cieloecommerce.cielo.com.br/)
- [Códigos de Retorno](https://developercielo.github.io/manual/cielo-ecommerce#c%C3%B3digos-de-retorno-das-transa%C3%A7%C3%B5es)

---

**Última atualização:** 2026-03-14  
**Total de fixtures:** 33  
**Arquivo:** `tests/Fixtures/CieloApiFixtures.php`
