<?php
/**
 * Testes 19-23: Estorno (Refund) de Transações
 * 
 * - Teste 19: Estorno total
 * - Teste 20: Estorno parcial
 * - Teste 21: Estorno de transação já cancelada
 * - Teste 22: Erro de rede no estorno
 * - Teste 23: Filtro customizado de estorno
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Refund
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Refund;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Brain\Monkey\Functions;
use Mockery;

class RefundProcessTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WooCommerce functions
        Functions\when('wc_get_logger')->justReturn($this->createMockLogger());
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        
        $this->gateway = new LknWCGatewayCieloCredit();
    }

    /**
     * @test
     * Teste 19.A: Estorno total bem-sucedido (Status 10)
     */
    public function test_full_refund_success()
    {
        // Arrange
        $orderId = 123;
        $orderAmount = 100.00;
        $refundAmount = 100.00; // Full refund
        $transactionId = 'TID123456789';

        Functions\when('get_option')->alias(function($key) {
            if (strpos($key, 'lkn_cielo_credit') !== false) {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no'
                ];
            }
            return 'sandbox';
        });

        // Mock order
        $mockOrder = $this->createMockOrder($orderId, $orderAmount);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn($transactionId);
        $mockOrder->shouldReceive('add_order_note')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        
        // Mock current_user_can
        Functions\when('current_user_can')->justReturn(true);

        // Mock has_filter
        Functions\when('has_filter')->justReturn(false);

        // Mock apply_filters - Return successful refund response
        Functions\when('apply_filters')->alias(function($filter, ...$args) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return [
                    'body' => json_encode([
                        'Status' => 10, // Cancelled/Refunded
                        'ReasonCode' => 0,
                        'ReasonMessage' => 'Successful'
                    ]),
                    'response' => ['code' => 200]
                ];
            }
            return $args[0] ?? null;
        });

        // Act
        $result = $this->gateway->process_refund($orderId, $refundAmount, 'Customer requested');

        // Assert
        $this->assertTrue($result, 'Full refund should be successful');
    }

    /**
     * @test
     * Teste 20.A: Estorno parcial bem-sucedido
     */
    public function test_partial_refund_success()
    {
        // Arrange
        $orderId = 456;
        $orderAmount = 200.00;
        $refundAmount = 100.00; // 50% partial refund

        Functions\when('get_option')->alias(function($key) {
            if (strpos($key, 'lkn_cielo_credit') !== false) {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no'
                ];
            }
            return 'sandbox';
        });

        $mockOrder = $this->createMockOrder($orderId, $orderAmount);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID789');
        $mockOrder->shouldReceive('add_order_note')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('has_filter')->justReturn(false);

        // Mock partial refund response (Status 2 or 11)
        Functions\when('apply_filters')->alias(function($filter, ...$args) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return [
                    'body' => json_encode([
                        'Status' => 11, // Partially Refunded
                        'ReasonCode' => 0,
                        'ReasonMessage' => 'Successful'
                    ]),
                    'response' => ['code' => 200]
                ];
            }
            return $args[0] ?? null;
        });

        // Act
        $result = $this->gateway->process_refund($orderId, $refundAmount, 'Partial refund');

        // Assert
        $this->assertTrue($result, 'Partial refund should be successful');
    }

    /**
     * @test
     * Teste 20.B: Múltiplos estornos parciais
     */
    public function test_multiple_partial_refunds()
    {
        // Arrange
        $orderId = 789;
        $orderAmount = 300.00;
        $refunds = [
            ['amount' => 100.00, 'reason' => 'First partial'],
            ['amount' => 50.00, 'reason' => 'Second partial'],
            ['amount' => 50.00, 'reason' => 'Third partial'],
        ];

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'no'
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, $orderAmount);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID999');
        $mockOrder->shouldReceive('add_order_note')->times(count($refunds));

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('has_filter')->justReturn(false);

        Functions\when('apply_filters')->alias(function($filter, ...$args) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return [
                    'body' => json_encode([
                        'Status' => 11,
                        'ReasonCode' => 0,
                        'ReasonMessage' => 'Successful'
                    ]),
                    'response' => ['code' => 200]
                ];
            }
            return $args[0] ?? null;
        });

        // Act - Process multiple refunds
        foreach ($refunds as $refund) {
            $result = $this->gateway->process_refund($orderId, $refund['amount'], $refund['reason']);
            
            // Assert each refund
            $this->assertTrue($result, "Refund of {$refund['amount']} should be successful");
        }
    }

    /**
     * @test
     * Teste 21.A: Estorno de transação já cancelada (erro)
     */
    public function test_refund_already_cancelled_transaction()
    {
        // Arrange
        $orderId = 111;

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'yes' // Enable debug for error logging
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID_CANCELLED');
        $mockOrder->shouldReceive('add_order_note')
            ->with(Mockery::on(function($note) {
                return strpos($note, 'refund failed') !== false;
            }))
            ->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('has_filter')->justReturn(false);

        // Mock response for already cancelled transaction
        Functions\when('apply_filters')->alias(function($filter, ...$args) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return [
                    'body' => json_encode([
                        'Status' => 3, // Denied/Already cancelled
                        'ReasonCode' => 99,
                        'ReasonMessage' => 'Transaction already cancelled'
                    ]),
                    'response' => ['code' => 400]
                ];
            }
            return $args[0] ?? null;
        });

        // Act
        $result = $this->gateway->process_refund($orderId, 100.00, 'Try to refund cancelled');

        // Assert
        $this->assertFalse($result, 'Refund of cancelled transaction should fail');
    }

    /**
     * @test
     * Teste 21.B: Estorno sem transaction ID
     */
    public function test_refund_without_transaction_id()
    {
        // Arrange
        $orderId = 222;

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'no'
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn(''); // Empty TID
        $mockOrder->shouldReceive('add_order_note')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('has_filter')->justReturn(false);

        Functions\when('apply_filters')->alias(function($filter, ...$args) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return [
                    'body' => json_encode([
                        'Status' => 0,
                        'ReasonMessage' => 'Invalid transaction ID'
                    ]),
                    'response' => ['code' => 400]
                ];
            }
            return $args[0] ?? null;
        });

        // Act
        $result = $this->gateway->process_refund($orderId, 100.00, 'No TID');

        // Assert
        $this->assertFalse($result, 'Refund without transaction ID should fail');
    }

    /**
     * @test
     * Teste 22.A: Erro de rede no estorno (timeout)
     */
    public function test_refund_network_timeout()
    {
        // Arrange
        $orderId = 333;

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'yes'
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID_TIMEOUT');
        $mockOrder->shouldReceive('add_order_note')
            ->with(Mockery::on(function($note) {
                return strpos($note, 'refund failed') !== false;
            }))
            ->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('has_filter')->justReturn(false);

        // Mock WP_Error for network timeout
        $wpError = Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_messages')
            ->andReturn(['Operation timed out after 120 seconds']);

        Functions\when('apply_filters')->justReturn($wpError);

        // Act
        $result = $this->gateway->process_refund($orderId, 100.00, 'Network timeout');

        // Assert
        $this->assertFalse($result, 'Refund with network timeout should fail');
    }

    /**
     * @test
     * Teste 22.B: Erro de conexão no estorno
     */
    public function test_refund_connection_error()
    {
        // Arrange
        $orderId = 444;

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'yes'
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID_CONN_ERROR');
        $mockOrder->shouldReceive('add_order_note')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('has_filter')->justReturn(false);

        // Mock WP_Error for connection error
        $wpError = Mockery::mock('WP_Error');
        $wpError->shouldReceive('get_error_messages')
            ->andReturn(['Failed to connect to API']);

        Functions\when('apply_filters')->justReturn($wpError);

        // Act
        $result = $this->gateway->process_refund($orderId, 100.00, 'Connection error');

        // Assert
        $this->assertFalse($result, 'Refund with connection error should fail');
    }

    /**
     * @test
     * Teste 23.A: Filtro customizado de estorno pode substituir lógica
     */
    public function test_refund_custom_filter_can_override()
    {
        // Arrange
        $orderId = 555;
        $customResponse = [
            'body' => json_encode([
                'Status' => 10,
                'ReasonMessage' => 'Custom refund logic applied',
                'CustomField' => 'test_value'
            ]),
            'response' => ['code' => 200]
        ];

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'yes'
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID_CUSTOM');
        $mockOrder->shouldReceive('add_order_note')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        Functions\when('current_user_can')->justReturn(true);
        
        // Simulate that filter is hooked
        Functions\when('has_filter')->alias(function($filter) {
            return $filter === 'lkn_wc_cielo_credit_refund';
        });

        // Mock apply_filters to return custom response
        Functions\when('apply_filters')->alias(function($filter, ...$args) use ($customResponse) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return $customResponse;
            }
            return $args[0] ?? null;
        });

        // Act
        $result = $this->gateway->process_refund($orderId, 100.00, 'Custom filter test');

        // Assert
        $this->assertTrue($result, 'Refund with custom filter should succeed');
    }

    /**
     * @test
     * Teste 23.B: Filtro de estorno sem permissão deve falhar
     */
    public function test_refund_filter_without_permission_fails()
    {
        // Arrange
        $orderId = 666;

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'yes'
            ];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID_NO_PERM');
        $mockOrder->shouldReceive('add_order_note')
            ->with(Mockery::on(function($note) {
                return strpos($note, 'insufficient permissions') !== false;
            }))
            ->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);
        
        // First check passes, second check (after filter) fails
        $permissionCheckCount = 0;
        Functions\when('current_user_can')->alias(function() use (&$permissionCheckCount) {
            $permissionCheckCount++;
            return $permissionCheckCount === 1; // First check passes, second fails
        });
        
        Functions\when('has_filter')->justReturn(true);
        
        Functions\when('apply_filters')->alias(function($filter, ...$args) {
            if ($filter === 'lkn_wc_cielo_credit_refund') {
                return [
                    'body' => json_encode(['Status' => 10]),
                    'response' => ['code' => 200]
                ];
            }
            return $args[0] ?? null;
        });

        // Act
        $result = $this->gateway->process_refund($orderId, 100.00, 'No permission');

        // Assert
        $this->assertFalse($result, 'Refund filter without permission should fail');
    }

    /**
     * @test
     * Teste 23.C: Status aceitos para estorno (10, 11, 2, 1)
     */
    public function test_refund_accepted_statuses()
    {
        // Arrange - All statuses that indicate successful refund
        $acceptedStatuses = [
            10 => 'Cancelled',
            11 => 'Partially Refunded',
            2 => 'Paid (can be refunded)',
            1 => 'Authorized (can be refunded)',
        ];

        Functions\when('get_option')->alias(function($key) {
            return [
                'env' => 'sandbox',
                'merchant_id' => 'test_merchant',
                'merchant_key' => 'test_key',
                'debug' => 'no'
            ];
        });

        foreach ($acceptedStatuses as $status => $description) {
            $mockOrder = $this->createMockOrder(1000 + $status, 100.00);
            $mockOrder->shouldReceive('get_transaction_id')->andReturn('TID_' . $status);
            $mockOrder->shouldReceive('add_order_note')->once();

            Functions\when('wc_get_order')->justReturn($mockOrder);
            Functions\when('current_user_can')->justReturn(true);
            Functions\when('has_filter')->justReturn(false);

            Functions\when('apply_filters')->alias(function($filter, ...$args) use ($status) {
                if ($filter === 'lkn_wc_cielo_credit_refund') {
                    return [
                        'body' => json_encode(['Status' => $status]),
                        'response' => ['code' => 200]
                    ];
                }
                return $args[0] ?? null;
            });

            // Act
            $result = $this->gateway->process_refund(1000 + $status, 100.00, 'Test status ' . $status);

            // Assert
            $this->assertTrue($result, "Status {$status} ({$description}) should be accepted for refund");
        }
    }
}
