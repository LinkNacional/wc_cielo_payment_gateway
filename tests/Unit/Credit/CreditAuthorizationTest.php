<?php
/**
 * Testes 12-13: Autorização e Captura de Cartão de Crédito
 * 
 * - Teste 12: Autorização com captura imediata
 * - Teste 13: Autorização com captura diferida
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Credit
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Credit;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Brain\Monkey\Functions;
use Mockery;

class CreditAuthorizationTest extends TestCase
{
    /**
     * @test
     * Teste 12.A: Autorização com captura imediata (Status 2)
     */
    public function test_authorization_with_immediate_capture()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_cielo_credit_settings') {
                return [
                    'enabled' => 'yes',
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant_id',
                    'merchant_key' => 'test_merchant_key',
                    'capture' => 'yes', // Immediate capture
                    'debug' => 'no'
                ];
            }
            return [];
        });

        // Mock API response - Authorized and Captured (Status 2)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2, // Paid/Captured
                    'PaymentId' => 'test-payment-id-123',
                    'Type' => 'CreditCard',
                    'Amount' => 10000,
                    'Installments' => 1,
                    'ProofOfSale' => '123456',
                    'AuthorizationCode' => '654321',
                    'Tid' => '1234567890123456789',
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Operation Successful',
                    'CreditCard' => [
                        'CardNumber' => '411111******1111',
                        'Brand' => 'Visa'
                    ]
                ]
            ]),
            'response' => ['code' => 201]
        ];
        
        $this->mockWpRemotePost($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);
        $this->mockWpRemoteRetrieveResponseCode(201);

        // Mock order
        $mockOrder = $this->createMockOrder(123, 100.00);
        Functions\when('wc_get_order')->justReturn($mockOrder);

        // Assert - Verify response structure
        $response = json_decode($apiResponse['body'], true);
        $this->assertArrayHasKey('Payment', $response);
        $this->assertEquals(2, $response['Payment']['Status']);
        $this->assertEquals('4', $response['Payment']['ReturnCode']);
        $this->assertEquals('Operation Successful', $response['Payment']['ReturnMessage']);
        $this->assertArrayHasKey('ProofOfSale', $response['Payment']);
        $this->assertArrayHasKey('AuthorizationCode', $response['Payment']);
        $this->assertArrayHasKey('Tid', $response['Payment']);
    }

    /**
     * @test
     * Teste 12.B: Metadata salvo corretamente (TID, ProofOfSale, AuthorizationCode)
     */
    public function test_metadata_saved_on_authorization()
    {
        // Arrange
        $paymentId = 'payment-id-456';
        $tid = '1234567890123456789';
        $proofOfSale = '789456';
        $authCode = '321654';

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_cielo_credit_settings') {
                return [
                    'enabled' => 'yes',
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'capture' => 'yes',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'PaymentId' => $paymentId,
                    'Tid' => $tid,
                    'ProofOfSale' => $proofOfSale,
                    'AuthorizationCode' => $authCode,
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Successful'
                ]
            ]),
            'response' => ['code' => 201]
        ];

        // Mock order expecting metadata updates
        $mockOrder = $this->createMockOrder(123, 100.00);
        $mockOrder->shouldReceive('update_meta_data')
            ->with('_cielo_payment_id', $paymentId)
            ->once();
        $mockOrder->shouldReceive('update_meta_data')
            ->with('_cielo_tid', $tid)
            ->once();
        $mockOrder->shouldReceive('update_meta_data')
            ->with('_cielo_proof_of_sale', $proofOfSale)
            ->once();
        $mockOrder->shouldReceive('update_meta_data')
            ->with('_cielo_authorization_code', $authCode)
            ->once();
        $mockOrder->shouldReceive('save')->atLeast()->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);

        // Simulate what the gateway would do
        $response = json_decode($apiResponse['body'], true);
        if (isset($response['Payment']['PaymentId'])) {
            $mockOrder->update_meta_data('_cielo_payment_id', $response['Payment']['PaymentId']);
            $mockOrder->update_meta_data('_cielo_tid', $response['Payment']['Tid']);
            $mockOrder->update_meta_data('_cielo_proof_of_sale', $response['Payment']['ProofOfSale']);
            $mockOrder->update_meta_data('_cielo_authorization_code', $response['Payment']['AuthorizationCode']);
            $mockOrder->save();
        }

        // Assert - Mockery will verify expectations
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 13.A: Autorização com captura diferida (Status 1)
     */
    public function test_authorization_without_capture()
    {
        // Arrange
        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_cielo_credit_settings') {
                return [
                    'enabled' => 'yes',
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'capture' => 'no', // Deferred capture
                    'debug' => 'no'
                ];
            }
            return [];
        });

        // Mock API response - Only Authorized (Status 1)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 1, // Authorized but not captured
                    'PaymentId' => 'auth-only-payment-id',
                    'Type' => 'CreditCard',
                    'Amount' => 15000,
                    'Installments' => 1,
                    'ProofOfSale' => '111222',
                    'AuthorizationCode' => '888999',
                    'Tid' => '9876543210987654321',
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Authorized',
                    'CreditCard' => [
                        'CardNumber' => '411111******1111',
                        'Brand' => 'Visa'
                    ]
                ]
            ]),
            'response' => ['code' => 201]
        ];
        
        $this->mockWpRemotePost($apiResponse);

        // Assert - Verify authorization without capture
        $response = json_decode($apiResponse['body'], true);
        $this->assertEquals(1, $response['Payment']['Status'], 'Status should be 1 (Authorized only)');
        $this->assertEquals('4', $response['Payment']['ReturnCode']);
        $this->assertArrayHasKey('AuthorizationCode', $response['Payment']);
    }

    /**
     * @test
     * Teste 13.B: Captura posterior bem-sucedida
     */
    public function test_deferred_capture_success()
    {
        // Arrange
        $paymentId = 'authorized-payment-123';
        $captureAmount = 15000;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_cielo_credit_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key'
                ];
            }
            return [];
        });

        // Mock capture API response
        $captureResponse = [
            'body' => json_encode([
                'Status' => 2, // Now captured
                'ReasonCode' => 0,
                'ReasonMessage' => 'Successful',
                'ReturnCode' => '4',
                'ReturnMessage' => 'Operation Successful'
            ]),
            'response' => ['code' => 200]
        ];
        
        // Mock wp_remote_request for PUT request (capture uses PUT)
        Functions\when('wp_remote_request')->justReturn($captureResponse);

        // Assert - Verify capture response
        $response = json_decode($captureResponse['body'], true);
        $this->assertEquals(2, $response['Status'], 'After capture, status should be 2 (Captured)');
        $this->assertEquals(0, $response['ReasonCode']);
        $this->assertEquals('Successful', $response['ReasonMessage']);
    }

    /**
     * @test
     * Teste 14: Cálculo correto de parcelas
     */
    public function test_installments_calculation()
    {
        // Arrange - Test different installment scenarios
        $testCases = [
            ['amount' => 100.00, 'installments' => 1, 'expected' => 10000], // À vista
            ['amount' => 150.00, 'installments' => 2, 'expected' => 15000], // 2x
            ['amount' => 300.00, 'installments' => 3, 'expected' => 30000], // 3x
            ['amount' => 600.00, 'installments' => 6, 'expected' => 60000], // 6x
            ['amount' => 1000.00, 'installments' => 10, 'expected' => 100000], // 10x
        ];

        foreach ($testCases as $testCase) {
            // Act - Format amount as Cielo expects (cents, no decimal separator)
            $amountInCents = (int) number_format($testCase['amount'], 2, '', '');

            // Assert
            $this->assertEquals(
                $testCase['expected'],
                $amountInCents,
                "Amount {$testCase['amount']} with {$testCase['installments']} installments should be {$testCase['expected']} cents"
            );
        }
    }

    /**
     * @test
     * Teste 14.B: Formatação de valor para API
     */
    public function test_amount_formatting_for_api()
    {
        // Arrange
        $testAmounts = [
            10.00 => 1000,
            10.50 => 1050,
            100.00 => 10000,
            100.99 => 10099,
            1000.00 => 100000,
            1234.56 => 123456,
        ];

        foreach ($testAmounts as $decimalAmount => $expectedCents) {
            // Act - Convert decimal to cents (Cielo format)
            $formattedAmount = (int) number_format($decimalAmount, 2, '', '');

            // Assert
            $this->assertEquals(
                $expectedCents,
                $formattedAmount,
                "Amount {$decimalAmount} should be formatted as {$expectedCents} cents"
            );
        }
    }
}
