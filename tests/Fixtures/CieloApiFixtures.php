<?php
/**
 * Cielo API Fixtures - Sandbox Responses
 * 
 * Fixtures abrangentes baseados na documentação oficial da API Cielo 3.0
 * Todos os cenários de sucesso, erro e edge cases para testes unitários
 * 
 * @see https://developercielo.github.io/manual/cielo-ecommerce
 * @package Lkn\WCCieloPaymentGateway\Tests\Fixtures
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Fixtures;

class CieloApiFixtures
{
    /**
     * Cartões de teste oficiais da Cielo Sandbox
     */
    const TEST_CARDS = [
        'visa_approved' => '4551870000000183',
        'visa_denied' => '4000000000000010',
        'mastercard_approved' => '5555666677778884',
        'mastercard_denied' => '5555666677778883',
        'amex_approved' => '376449047333005',
        'elo_approved' => '6362970000457013',
        'diners_approved' => '36490102462661',
        'hipercard_approved' => '6062825624254001',
    ];

    /**
     * Dados de cliente para testes
     */
    const TEST_CUSTOMER = [
        'name' => 'João da Silva',
        'cpf' => '12345678900',
        'email' => 'test@cielo.com.br',
        'phone' => '11987654321',
    ];

    /**
     * PIX - Pagamento criado com sucesso (Status 12 - Pending)
     */
    public static function pixCreated(): array
    {
        return [
            'body' => json_encode([
                'MerchantOrderId' => 'ORDER-12345',
                'Customer' => [
                    'Name' => self::TEST_CUSTOMER['name'],
                ],
                'Payment' => [
                    'ServiceTaxAmount' => 0,
                    'Installments' => 1,
                    'Interest' => 0,
                    'Capture' => false,
                    'Authenticate' => false,
                    'Recurrent' => false,
                    'Provider' => 'Cielo30',
                    'Amount' => 10000,
                    'ReceivedDate' => '2024-01-15 10:30:00',
                    'Status' => 12, // Pending
                    'IsSplitted' => false,
                    'ReturnMessage' => 'Pix gerado com sucesso',
                    'ReturnCode' => '0',
                    'PaymentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'Type' => 'Pix',
                    'Currency' => 'BRL',
                    'Country' => 'BRA',
                    'QrCodeBase64Image' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    'QrCodeString' => '00020126330014BR.GOV.BCB.PIX0111012345678905204000053039865802BR5925MERCHANT NAME6009SAO PAULO62070503***63041234',
                    'Links' => [
                        [
                            'Method' => 'GET',
                            'Rel' => 'self',
                            'Href' => 'https://apiquerysandbox.cieloecommerce.cielo.com.br/1/sales/a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                        ],
                    ],
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * PIX - Pagamento confirmado (Status 2 - Paid)
     */
    public static function pixPaid(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'ServiceTaxAmount' => 0,
                    'Installments' => 1,
                    'Interest' => 0,
                    'Capture' => false,
                    'Authenticate' => false,
                    'Recurrent' => false,
                    'Provider' => 'Cielo30',
                    'Amount' => 10000,
                    'ReceivedDate' => '2024-01-15 10:30:00',
                    'CapturedDate' => '2024-01-15 10:31:00',
                    'Status' => 2, // PaymentConfirmed
                    'IsSplitted' => false,
                    'ReturnMessage' => 'Pix confirmado',
                    'ReturnCode' => '0',
                    'PaymentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'Type' => 'Pix',
                    'Currency' => 'BRL',
                    'Country' => 'BRA',
                ],
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * PIX - Pagamento expirado (Status 3 - Denied)
     */
    public static function pixExpired(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 3, // Denied
                    'ReturnCode' => '99',
                    'ReturnMessage' => 'Pix expirado',
                    'PaymentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'Type' => 'Pix',
                    'Amount' => 10000,
                ],
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * PIX - Pagamento cancelado (Status 10 - Voided)
     */
    public static function pixCancelled(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 10, // Voided/Cancelled
                    'ReturnCode' => '0',
                    'ReturnMessage' => 'Cancelamento realizado com sucesso',
                    'PaymentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'Type' => 'Pix',
                    'Amount' => 10000,
                ],
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * Credit Card - Autorização bem-sucedida (Status 1 - Authorized)
     */
    public static function creditAuthorized(): array
    {
        return [
            'body' => json_encode([
                'MerchantOrderId' => 'ORDER-67890',
                'Customer' => [
                    'Name' => self::TEST_CUSTOMER['name'],
                ],
                'Payment' => [
                    'ServiceTaxAmount' => 0,
                    'Installments' => 1,
                    'Interest' => 'ByMerchant',
                    'Capture' => false,
                    'Authenticate' => false,
                    'Recurrent' => false,
                    'Provider' => 'Simulado',
                    'CreditCard' => [
                        'CardNumber' => '455187******0183',
                        'Holder' => 'Teste Holder',
                        'ExpirationDate' => '12/2030',
                        'SaveCard' => false,
                        'Brand' => 'Visa',
                    ],
                    'ProofOfSale' => '123456',
                    'AcquirerTransactionId' => '0115123456789',
                    'AuthorizationCode' => '654321',
                    'SoftDescriptor' => 'Loja Teste',
                    'PaymentId' => 'b2c3d4e5-f6a7-8901-bcde-f12345678901',
                    'Type' => 'CreditCard',
                    'Amount' => 15000,
                    'ReceivedDate' => '2024-01-15 11:00:00',
                    'Currency' => 'BRL',
                    'Country' => 'BRA',
                    'Provider' => 'Simulado',
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Operation Successful',
                    'Status' => 1, // Authorized (not captured)
                    'Tid' => '1234567890123456789',
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Credit Card - Autorização e captura imediata (Status 2 - Paid)
     */
    public static function creditPaid(): array
    {
        return [
            'body' => json_encode([
                'MerchantOrderId' => 'ORDER-67890',
                'Customer' => [
                    'Name' => self::TEST_CUSTOMER['name'],
                ],
                'Payment' => [
                    'ServiceTaxAmount' => 0,
                    'Installments' => 3,
                    'Interest' => 'ByMerchant',
                    'Capture' => true,
                    'Authenticate' => false,
                    'Recurrent' => false,
                    'Provider' => 'Simulado',
                    'CreditCard' => [
                        'CardNumber' => '455187******0183',
                        'Holder' => 'Teste Holder',
                        'ExpirationDate' => '12/2030',
                        'SaveCard' => false,
                        'Brand' => 'Visa',
                    ],
                    'ProofOfSale' => '654321',
                    'AcquirerTransactionId' => '0115654321000',
                    'AuthorizationCode' => '123456',
                    'SoftDescriptor' => 'Loja Teste',
                    'PaymentId' => 'c3d4e5f6-a7b8-9012-cdef-123456789012',
                    'Type' => 'CreditCard',
                    'Amount' => 30000,
                    'ReceivedDate' => '2024-01-15 12:00:00',
                    'CapturedDate' => '2024-01-15 12:00:01',
                    'CapturedAmount' => 30000,
                    'Currency' => 'BRL',
                    'Country' => 'BRA',
                    'Provider' => 'Simulado',
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Operation Successful',
                    'Status' => 2, // PaymentConfirmed (captured)
                    'Tid' => '9876543210987654321',
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Credit Card - Negado por saldo insuficiente (Status 3, Code 51)
     */
    public static function creditDeniedInsufficientFunds(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'ServiceTaxAmount' => 0,
                    'Installments' => 1,
                    'Interest' => 'ByMerchant',
                    'Capture' => true,
                    'Authenticate' => false,
                    'Recurrent' => false,
                    'Provider' => 'Simulado',
                    'CreditCard' => [
                        'CardNumber' => '555566******8884',
                        'Holder' => 'Teste Holder',
                        'ExpirationDate' => '12/2030',
                        'SaveCard' => false,
                        'Brand' => 'Master',
                    ],
                    'PaymentId' => 'd4e5f6a7-b8c9-0123-def0-234567890123',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'ReceivedDate' => '2024-01-15 13:00:00',
                    'Currency' => 'BRL',
                    'Country' => 'BRA',
                    'Provider' => 'Simulado',
                    'ReturnCode' => '51',
                    'ReturnMessage' => 'Insufficient Funds',
                    'Status' => 3, // Denied
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Credit Card - Negado por cartão expirado (Status 3, Code 57)
     */
    public static function creditDeniedExpired(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'PaymentId' => 'e5f6a7b8-c9d0-1234-ef01-345678901234',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'ReturnCode' => '57',
                    'ReturnMessage' => 'Card Expired',
                    'Status' => 3, // Denied
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Credit Card - Negado por cartão bloqueado (Status 3, Code 78)
     */
    public static function creditDeniedBlocked(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'PaymentId' => 'f6a7b8c9-d0e1-2345-f012-456789012345',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'ReturnCode' => '78',
                    'ReturnMessage' => 'Card Blocked',
                    'Status' => 3, // Denied
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Credit Card - Negado genérico (Status 3, Code 05)
     */
    public static function creditDeniedGeneric(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'PaymentId' => 'a7b8c9d0-e1f2-3456-0123-567890123456',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'ReturnCode' => '05',
                    'ReturnMessage' => 'Not Authorized',
                    'Status' => 3, // Denied
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Credit Card - Timeout (Status 3, Code 99)
     */
    public static function creditTimeout(): array
    {
        return [
            'body' => json_encode([
                'Payment' => [
                    'PaymentId' => 'b8c9d0e1-f2a3-4567-1234-678901234567',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'ReturnCode' => '99',
                    'ReturnMessage' => 'Time Out',
                    'Status' => 3, // Denied
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Debit Card - Autorizado e redirecionado para autenticação
     */
    public static function debitAuthenticationRedirect(): array
    {
        return [
            'body' => json_encode([
                'MerchantOrderId' => 'ORDER-11111',
                'Payment' => [
                    'DebitCard' => [
                        'CardNumber' => '455187******0183',
                        'Holder' => 'Teste Holder',
                        'ExpirationDate' => '12/2030',
                        'SaveCard' => false,
                        'Brand' => 'Visa',
                    ],
                    'AuthenticationUrl' => 'https://qasecommerce.cielo.com.br/web/index.cbmp?id=abc123',
                    'Tid' => '1234567890123456789',
                    'PaymentId' => 'c9d0e1f2-a3b4-5678-2345-789012345678',
                    'Type' => 'DebitCard',
                    'Amount' => 10000,
                    'ReceivedDate' => '2024-01-15 14:00:00',
                    'Currency' => 'BRL',
                    'Country' => 'BRA',
                    'ReturnCode' => '0',
                    'ReturnMessage' => 'Authenticated',
                    'Status' => 0, // NotFinished (aguardando autenticação)
                ],
            ]),
            'response' => ['code' => 201],
        ];
    }

    /**
     * Refund - Estorno total bem-sucedido (Status 10)
     */
    public static function refundFullSuccess(): array
    {
        return [
            'body' => json_encode([
                'Status' => 10, // Voided/Cancelled
                'ReasonCode' => 0,
                'ReasonMessage' => 'Successful',
                'ProviderReasonCode' => '0',
                'ProviderReasonMessage' => 'Operation Successful',
                'ReturnCode' => '0',
                'ReturnMessage' => 'Refund successful',
                'Links' => [
                    [
                        'Method' => 'GET',
                        'Rel' => 'self',
                        'Href' => 'https://apiquerysandbox.cieloecommerce.cielo.com.br/1/sales/xyz',
                    ],
                ],
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * Refund - Estorno parcial bem-sucedido (Status 11)
     */
    public static function refundPartialSuccess(): array
    {
        return [
            'body' => json_encode([
                'Status' => 11, // Refunded (parcial)
                'ReasonCode' => 0,
                'ReasonMessage' => 'Successful',
                'ProviderReasonCode' => '0',
                'ProviderReasonMessage' => 'Partial Refund',
                'ReturnCode' => '0',
                'ReturnMessage' => 'Partial refund successful',
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * Refund - Estorno negado (transação já cancelada)
     */
    public static function refundAlreadyCancelled(): array
    {
        return [
            'body' => json_encode([
                'Status' => 10,
                'ReasonCode' => 99,
                'ReasonMessage' => 'Transaction already cancelled',
                'ReturnCode' => '99',
                'ReturnMessage' => 'Cannot refund cancelled transaction',
            ]),
            'response' => ['code' => 400],
        ];
    }

    /**
     * Captura - Captura diferida bem-sucedida
     */
    public static function captureSuccess(): array
    {
        return [
            'body' => json_encode([
                'Status' => 2, // PaymentConfirmed
                'ReasonCode' => 0,
                'ReasonMessage' => 'Successful',
                'ReturnCode' => '4',
                'ReturnMessage' => 'Operation Successful',
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * Zero Auth - Validação de cartão bem-sucedida
     */
    public static function zeroAuthValid(): array
    {
        return [
            'body' => json_encode([
                'Valid' => true,
                'ReturnCode' => '00',
                'ReturnMessage' => 'Transacao autorizada',
                'IssuerTransactionId' => '1234567890',
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * Zero Auth - Validação de cartão inválido
     */
    public static function zeroAuthInvalid(): array
    {
        return [
            'body' => json_encode([
                'Valid' => false,
                'ReturnCode' => '57',
                'ReturnMessage' => 'Cartao expirado',
            ]),
            'response' => ['code' => 200],
        ];
    }

    /**
     * Erro - Credenciais inválidas (Code 129)
     */
    public static function errorInvalidCredentials(): array
    {
        return [
            'body' => json_encode([
                [
                    'Code' => 129,
                    'Message' => 'MerchantId is required',
                ],
            ]),
            'response' => ['code' => 401],
        ];
    }

    /**
     * Erro - Merchant inválido (Code 132)
     */
    public static function errorInvalidMerchant(): array
    {
        return [
            'body' => json_encode([
                [
                    'Code' => 132,
                    'Message' => 'MerchantId invalid',
                ],
            ]),
            'response' => ['code' => 401],
        ];
    }

    /**
     * Erro - Requisição inválida (Code 101)
     */
    public static function errorInvalidRequest(): array
    {
        return [
            'body' => json_encode([
                [
                    'Code' => 101,
                    'Message' => 'Invalid request',
                ],
            ]),
            'response' => ['code' => 400],
        ];
    }

    /**
     * Erro - Pagamento não encontrado
     */
    public static function errorPaymentNotFound(): array
    {
        return [
            'body' => json_encode([
                [
                    'Code' => 404,
                    'Message' => 'Payment not found',
                ],
            ]),
            'response' => ['code' => 404],
        ];
    }

    /**
     * Erro - Erro interno do servidor
     */
    public static function errorInternalServer(): array
    {
        return [
            'body' => json_encode([
                'Code' => 500,
                'Message' => 'Internal Server Error',
            ]),
            'response' => ['code' => 500],
        ];
    }

    /**
     * Erro - Serviço indisponível
     */
    public static function errorServiceUnavailable(): array
    {
        return [
            'body' => json_encode([
                'Code' => 503,
                'Message' => 'Service Temporarily Unavailable',
            ]),
            'response' => ['code' => 503],
        ];
    }

    /**
     * Erro - Rate limit excedido
     */
    public static function errorRateLimitExceeded(): array
    {
        return [
            'body' => json_encode([
                'Code' => 429,
                'Message' => 'Too Many Requests',
            ]),
            'response' => ['code' => 429],
        ];
    }

    /**
     * Erro - Timeout de rede (WP_Error)
     */
    public static function errorNetworkTimeout(): \WP_Error
    {
        return new \WP_Error(
            'http_request_failed',
            'Operation timed out after 120000 milliseconds'
        );
    }

    /**
     * Erro - Conexão recusada (WP_Error)
     */
    public static function errorConnectionRefused(): \WP_Error
    {
        return new \WP_Error(
            'http_request_failed',
            'Failed to connect to api.cieloecommerce.cielo.com.br'
        );
    }

    /**
     * Erro - SSL/TLS erro (WP_Error)
     */
    public static function errorSslError(): \WP_Error
    {
        return new \WP_Error(
            'http_request_failed',
            'SSL certificate problem: unable to get local issuer certificate'
        );
    }

    /**
     * Get fixture by name - Helper method
     * 
     * @param string $name Nome do fixture
     * @return mixed
     */
    public static function get(string $name)
    {
        $method = str_replace('-', '', ucwords($name, '-'));
        $method = lcfirst(str_replace('_', '', ucwords($method, '_')));
        
        if (method_exists(self::class, $method)) {
            return self::$method();
        }
        
        throw new \InvalidArgumentException("Fixture '{$name}' not found");
    }

    /**
     * Get all available fixture names
     * 
     * @return array
     */
    public static function getAvailableFixtures(): array
    {
        $reflection = new \ReflectionClass(self::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC);
        
        $fixtures = [];
        foreach ($methods as $method) {
            $name = $method->getName();
            if ($name !== 'get' && $name !== 'getAvailableFixtures' && !in_array($name, ['TEST_CARDS', 'TEST_CUSTOMER'])) {
                $fixtures[] = $name;
            }
        }
        
        return $fixtures;
    }
}
