<?php
/**
 * Test Helper Functions
 * 
 * Funções auxiliares para facilitar a criação de mocks e fixtures nos testes
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests
 */

namespace Lkn\WCCieloPaymentGateway\Tests;

/**
 * Create a mock WP_Error object
 *
 * @param string $code Error code
 * @param string $message Error message
 * @return \Mockery\MockInterface
 */
function mockWpError($code = 'error', $message = 'Error message')
{
    $error = \Mockery::mock('WP_Error');
    $error->shouldReceive('get_error_code')->andReturn($code);
    $error->shouldReceive('get_error_message')->andReturn($message);
    $error->shouldReceive('get_error_messages')->andReturn([$message]);
    return $error;
}

/**
 * Create a mock WC_Order object
 *
 * @param int $orderId Order ID
 * @param float $total Order total
 * @return \Mockery\MockInterface
 */
function mockWcOrder($orderId = 123, $total = 100.00)
{
    $order = \Mockery::mock('WC_Order');
    $order->shouldReceive('get_id')->andReturn($orderId);
    $order->shouldReceive('get_total')->andReturn($total);
    $order->shouldReceive('get_currency')->andReturn('BRL');
    $order->shouldReceive('get_order_number')->andReturn($orderId);
    $order->shouldReceive('get_transaction_id')->andReturn('TID' . $orderId);
    
    return $order;
}

/**
 * Create a mock WC_Logger object
 *
 * @return \Mockery\MockInterface
 */
function mockWcLogger()
{
    $logger = \Mockery::mock('WC_Logger');
    $logger->shouldReceive('log')->andReturn(true);
    $logger->shouldReceive('info')->andReturn(true);
    $logger->shouldReceive('error')->andReturn(true);
    $logger->shouldReceive('debug')->andReturn(true);
    $logger->shouldReceive('warning')->andReturn(true);
    
    return $logger;
}

/**
 * Get fixture data for Cielo API responses
 *
 * @param string $type Response type (success, error, pending, etc)
 * @return array
 */
function getCieloApiFixture($type = 'success')
{
    $fixtures = [
        'pix_success' => [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 12, // Pending
                    'PaymentId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                    'Type' => 'Pix',
                    'Amount' => 10000,
                    'QrCodeBase64Image' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                    'QrCodeString' => '00020126330014BR.GOV.BCB.PIX0111012345678905204000053039865802BR5925MERCHANT NAME6009SAO PAULO62070503***63041234'
                ]
            ]),
            'response' => ['code' => 201]
        ],
        'credit_success' => [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2, // Paid/Authorized
                    'PaymentId' => 'b2c3d4e5-f6a7-8901-bcde-f12345678901',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'Installments' => 1,
                    'ProofOfSale' => '123456',
                    'AuthorizationCode' => '654321',
                    'Tid' => '1234567890123456789',
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Operation Successful'
                ]
            ]),
            'response' => ['code' => 201]
        ],
        'credit_denied' => [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 3, // Denied
                    'PaymentId' => 'c3d4e5f6-a7b8-9012-cdef-123456789012',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'ReturnCode' => '57',
                    'ReturnMessage' => 'Card Expired'
                ]
            ]),
            'response' => ['code' => 201]
        ],
        'refund_success' => [
            'body' => json_encode([
                'Status' => 10, // Cancelled
                'ReasonCode' => 0,
                'ReasonMessage' => 'Successful',
                'Links' => []
            ]),
            'response' => ['code' => 200]
        ],
        'api_error' => [
            'body' => json_encode([
                'Code' => 500,
                'Message' => 'Internal Server Error'
            ]),
            'response' => ['code' => 500]
        ],
        'network_error' => new \WP_Error('http_request_failed', 'A valid URL was not provided.')
    ];

    return $fixtures[$type] ?? $fixtures['credit_success'];
}

/**
 * Get masked card number for testing
 *
 * @param string $cardNumber Original card number
 * @return string Masked card number
 */
function getMaskedCardNumber($cardNumber)
{
    return substr($cardNumber, 0, 6) . str_repeat('*', strlen($cardNumber) - 10) . substr($cardNumber, -4);
}

/**
 * Get masked CPF/CNPJ for testing
 *
 * @param string $document Original document
 * @return string Masked document
 */
function getMaskedDocument($document)
{
    $clean = preg_replace('/[^0-9]/', '', $document);
    
    if (strlen($clean) == 11) { // CPF
        return substr($clean, 0, 3) . '.***.***-' . substr($clean, -2);
    } elseif (strlen($clean) == 14) { // CNPJ
        return substr($clean, 0, 2) . '.***.***/****-' . substr($clean, -2);
    }
    
    return $document;
}
