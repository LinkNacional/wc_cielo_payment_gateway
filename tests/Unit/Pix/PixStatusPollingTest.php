<?php
/**
 * Teste 03: Polling de Status PIX
 * 
 * Testa a verificação periódica do status do pagamento PIX
 * Valida diferentes status retornados pela API Cielo
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Pix
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Brain\Monkey\Functions;
use Mockery;

class PixStatusPollingTest extends TestCase
{
    /**
     * @test
     * Teste 03.A: Status PIX - Pendente (Status 12)
     */
    public function test_pix_status_pending()
    {
        // Arrange
        $paymentId = 'pending-payment-id';
        $orderId = 123;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no',
                    'payment_complete_status' => 'processing'
                ];
            }
            return [];
        });

        // Mock API response - Status 12 (Pending)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 12,
                    'PaymentId' => $paymentId,
                    'Type' => 'Pix'
                ]
            ]),
            'response' => ['code' => 200]
        ];
        
        $this->mockWpRemoteGet($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);

        // Mock order
        $mockOrder = $this->createMockOrder($orderId);
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('update_status')->with('pending')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\expect('wp_schedule_single_event')->once()->andReturn(true);

        // Act
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Order status should remain pending
        // Expectations are verified by Mockery
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 03.B: Status PIX - Pago (Status 2)
     */
    public function test_pix_status_paid()
    {
        // Arrange
        $paymentId = 'paid-payment-id';
        $orderId = 456;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no',
                    'payment_complete_status' => 'processing'
                ];
            }
            return [];
        });

        // Mock API response - Status 2 (Paid)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'PaymentId' => $paymentId,
                    'Type' => 'Pix',
                    'Amount' => 10000
                ]
            ]),
            'response' => ['code' => 200]
        ];
        
        $this->mockWpRemoteGet($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);

        // Mock order expecting status update to processing
        $mockOrder = $this->createMockOrder($orderId);
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('update_status')->with('processing')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\expect('wp_schedule_single_event')->once()->andReturn(true);

        // Act
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Order status should be updated to processing
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 03.C: Status PIX - Cancelado (Status 3)
     */
    public function test_pix_status_cancelled()
    {
        // Arrange
        $paymentId = 'cancelled-payment-id';
        $orderId = 789;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no',
                    'payment_complete_status' => 'processing'
                ];
            }
            return [];
        });

        // Mock API response - Status 3 (Denied/Cancelled)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 3,
                    'PaymentId' => $paymentId,
                    'Type' => 'Pix'
                ]
            ]),
            'response' => ['code' => 200]
        ];
        
        $this->mockWpRemoteGet($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);

        // Mock order expecting status update to cancelled
        $mockOrder = $this->createMockOrder($orderId);
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('update_status')->with('cancelled')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\expect('wp_schedule_single_event')->once()->andReturn(true);

        // Act
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Order status should be cancelled
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 03.D: Status PIX - Estornado (Status 10)
     */
    public function test_pix_status_refunded()
    {
        // Arrange
        $paymentId = 'refunded-payment-id';
        $orderId = 999;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no',
                    'payment_complete_status' => 'processing'
                ];
            }
            return [];
        });

        // Mock API response - Status 10 (Cancelled/Refunded)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 10,
                    'PaymentId' => $paymentId,
                    'Type' => 'Pix'
                ]
            ]),
            'response' => ['code' => 200]
        ];
        
        $this->mockWpRemoteGet($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);

        // Mock order expecting status update to cancelled
        $mockOrder = $this->createMockOrder($orderId);
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('update_status')->with('cancelled')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\expect('wp_schedule_single_event')->once()->andReturn(true);

        // Act
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Order status should be cancelled
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 03.E: Status PIX - Resposta inválida da API
     */
    public function test_pix_status_invalid_response()
    {
        // Arrange
        $paymentId = 'invalid-response-payment-id';
        $orderId = 111;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no',
                    'payment_complete_status' => 'processing'
                ];
            }
            return [];
        });

        // Mock API response - Invalid response (missing Payment key)
        $apiResponse = [
            'body' => json_encode([
                'Error' => 'Invalid PaymentId'
            ]),
            'response' => ['code' => 404]
        ];
        
        $this->mockWpRemoteGet($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);

        // Mock order expecting status update to cancelled (default for invalid response)
        $mockOrder = $this->createMockOrder($orderId);
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('update_status')->with('cancelled')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\expect('wp_schedule_single_event')->once()->andReturn(true);

        // Act
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Should default to cancelled status
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 03.F: Status PIX - Não atualiza se pedido não está pendente
     */
    public function test_pix_status_does_not_update_if_not_pending()
    {
        // Arrange
        $paymentId = 'completed-order-payment-id';
        $orderId = 222;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no',
                    'payment_complete_status' => 'processing'
                ];
            }
            return [];
        });

        // Mock API response - Status 2 (Paid)
        $apiResponse = [
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'PaymentId' => $paymentId,
                    'Type' => 'Pix'
                ]
            ]),
            'response' => ['code' => 200]
        ];
        
        $this->mockWpRemoteGet($apiResponse);
        $this->mockWpRemoteRetrieveBody($apiResponse['body']);

        // Mock order that is already completed (not pending)
        $mockOrder = $this->createMockOrder($orderId);
        $mockOrder->shouldReceive('get_status')->andReturn('completed');
        // update_status should NOT be called
        $mockOrder->shouldNotReceive('update_status');

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\expect('wp_schedule_single_event')->once()->andReturn(true);

        // Act
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Status should not be updated
        $this->assertTrue(true);
    }
}
