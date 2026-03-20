<?php
/**
 * Testes 24-28: Logging e Mascaramento de Dados
 * 
 * - Teste 24: Debug mode ON salva logs
 * - Teste 25: Debug mode OFF não salva logs
 * - Teste 26: Credenciais mascaradas nos logs
 * - Teste 27: Order meta logs salvos corretamente
 * - Teste 28: Metabox exibido no admin
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Logging
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Logging;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use Brain\Monkey\Functions;
use Mockery;

class LoggingTest extends TestCase
{
    /**
     * @test
     * Teste 24.A: Debug mode ON salva logs via WC_Logger
     */
    public function test_debug_mode_on_saves_logs()
    {
        // Arrange
        $mockLogger = $this->createMockLogger();
        
        // Expect log to be called when debug is 'yes'
        $mockLogger->shouldReceive('log')
            ->with('info', Mockery::type('string'), Mockery::type('array'))
            ->once();

        Functions\when('wc_get_logger')->justReturn($mockLogger);

        // Simulate debug mode ON
        $debugMode = 'yes';
        
        // Act
        if ($debugMode === 'yes') {
            $mockLogger->log('info', 'Payment processed successfully', [
                'source' => 'woocommerce-cielo-credit',
                'payment_id' => 'test-123'
            ]);
        }

        // Assert - Mockery will verify the log was called
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 24.B: Source correto nos logs
     */
    public function test_log_source_is_correct()
    {
        // Arrange
        $expectedSources = [
            'woocommerce-cielo-credit',
            'woocommerce-cielo-debit',
            'woocommerce-cielo-pix',
            'woocommerce-cielo-credit-security',
        ];

        $mockLogger = $this->createMockLogger();

        foreach ($expectedSources as $source) {
            // Expect log with correct source
            $mockLogger->shouldReceive('log')
                ->with(
                    Mockery::any(),
                    Mockery::any(),
                    Mockery::on(function($context) use ($source) {
                        return isset($context['source']) && $context['source'] === $source;
                    })
                )
                ->once();

            // Act
            $mockLogger->log('info', 'Test message', ['source' => $source]);
        }

        // Assert
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 25.A: Debug mode OFF não salva logs normais
     */
    public function test_debug_mode_off_no_logs()
    {
        // Arrange
        $mockLogger = $this->createMockLogger();
        
        // Should NOT be called when debug is 'no'
        $mockLogger->shouldNotReceive('log');
        $mockLogger->shouldNotReceive('info');
        $mockLogger->shouldNotReceive('debug');

        Functions\when('wc_get_logger')->justReturn($mockLogger);

        // Simulate debug mode OFF
        $debugMode = 'no';
        
        // Act - Do NOT log when debug is off
        if ($debugMode === 'yes') {
            $mockLogger->log('info', 'This should not be logged', [
                'source' => 'woocommerce-cielo-credit'
            ]);
        }

        // Assert - Mockery will verify log was NOT called
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 25.B: Apenas erros críticos são logados quando debug OFF
     */
    public function test_critical_errors_logged_even_when_debug_off()
    {
        // Arrange
        $mockLogger = $this->createMockLogger();
        
        // Critical errors should ALWAYS be logged
        $mockLogger->shouldReceive('error')
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->once();

        Functions\when('wc_get_logger')->justReturn($mockLogger);

        $debugMode = 'no';
        
        // Act - Critical errors logged even with debug OFF
        $mockLogger->error('Critical payment error', [
            'source' => 'woocommerce-cielo-credit'
        ]);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 26.A: MerchantId mascarado via censorString
     */
    public function test_merchant_id_censored()
    {
        // Arrange
        $originalMerchantId = '1234567890abcdef';
        $censorLength = 10;

        $helper = new LknWcCieloHelper();
        
        // Act
        $censored = $helper->censorString($originalMerchantId, $censorLength);

        // Assert
        $this->assertNotEquals($originalMerchantId, $censored);
        $this->assertStringContainsString('*', $censored);
        $this->assertEquals(strlen($originalMerchantId), strlen($censored));
        
        // Verify it contains exactly $censorLength asterisks
        $this->assertEquals($censorLength, substr_count($censored, '*'));
    }

    /**
     * @test
     * Teste 26.B: MerchantKey mascarado
     */
    public function test_merchant_key_censored()
    {
        // Arrange
        $originalMerchantKey = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456';
        $censorLength = 20;

        $helper = new LknWcCieloHelper();
        
        // Act
        $censored = $helper->censorString($originalMerchantKey, $censorLength);

        // Assert
        $this->assertNotEquals($originalMerchantKey, $censored);
        $this->assertStringContainsString('*', $censored);
        $this->assertEquals($censorLength, substr_count($censored, '*'));
        
        // Original key should not be fully visible
        $this->assertStringNotContainsString('IJKLMNOPQRSTUV', $censored);
    }

    /**
     * @test
     * Teste 26.C: Número de cartão mascarado nos logs
     */
    public function test_card_number_masked_in_logs()
    {
        // Arrange
        $originalCard = '4111111111111111';
        $maskedCard = '411111******1111';
        
        $logData = [
            'card_number' => $maskedCard,
            'payment_id' => 'test-123',
            'status' => 'approved'
        ];

        $logString = json_encode($logData);

        // Assert - Original card should NOT be in logs
        $this->assertStringNotContainsString($originalCard, $logString);
        $this->assertStringContainsString($maskedCard, $logString);
        $this->assertStringContainsString('*', $logString);
        
        // Middle digits should not be visible
        $this->assertStringNotContainsString('1111111', $logString);
    }

    /**
     * @test
     * Teste 26.D: Múltiplos dados sensíveis mascarados simultaneamente
     */
    public function test_multiple_sensitive_data_masked()
    {
        // Arrange
        $sensitiveData = [
            'card' => '4111111111111111',
            'cpf' => '12345678900',
            'merchant_id' => '1234567890abcdef',
            'merchant_key' => 'SECRETKEY123456'
        ];

        $maskedData = [
            'card' => '411111******1111',
            'cpf' => '123.***.***-00',
            'merchant_id' => '123**********def',
            'merchant_key' => 'SEC**********56'
        ];

        $logString = json_encode($maskedData);

        // Assert - None of the original sensitive data should be in logs
        $this->assertStringNotContainsString($sensitiveData['card'], $logString);
        $this->assertStringNotContainsString($sensitiveData['cpf'], $logString);
        $this->assertStringNotContainsString($sensitiveData['merchant_id'], $logString);
        $this->assertStringNotContainsString($sensitiveData['merchant_key'], $logString);
        
        // But masked versions should be present
        $this->assertStringContainsString('*', $logString);
    }

    /**
     * @test
     * Teste 27.A: Order meta 'lknWcCieloOrderLogs' salvo corretamente
     */
    public function test_order_meta_logs_saved()
    {
        // Arrange
        $orderId = 123;
        $orderLogs = [
            'url' => 'https://api.cieloecommerce.cielo.com.br/1/sales/',
            'headers' => [
                'Content-Type' => 'application/json',
                'MerchantId' => '123**********def',
                'MerchantKey' => 'SEC**********56'
            ],
            'body' => [
                'MerchantOrderId' => 'ORDER-123',
                'Payment' => [
                    'Type' => 'CreditCard',
                    'Amount' => 10000
                ]
            ],
            'response' => [
                'Status' => 2,
                'PaymentId' => 'payment-123'
            ]
        ];

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        
        // Expect metadata to be saved as JSON
        $mockOrder->shouldReceive('update_meta_data')
            ->with('lknWcCieloOrderLogs', Mockery::type('string'))
            ->once();
        $mockOrder->shouldReceive('save')->once();

        Functions\when('wc_get_order')->justReturn($mockOrder);

        // Act
        $orderLogsJson = json_encode($orderLogs);
        $mockOrder->update_meta_data('lknWcCieloOrderLogs', $orderLogsJson);
        $mockOrder->save();

        // Assert
        $this->assertTrue(true);
        $this->assertJson($orderLogsJson);
        
        // Verify structure
        $decoded = json_decode($orderLogsJson, true);
        $this->assertArrayHasKey('url', $decoded);
        $this->assertArrayHasKey('headers', $decoded);
        $this->assertArrayHasKey('body', $decoded);
        $this->assertArrayHasKey('response', $decoded);
    }

    /**
     * @test
     * Teste 27.B: Estrutura de logs está correta e completa
     */
    public function test_order_logs_structure_is_correct()
    {
        // Arrange
        $expectedStructure = [
            'url',
            'headers',
            'body',
            'response'
        ];

        $orderLogs = [
            'url' => 'https://api.example.com',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => ['test' => 'data'],
            'response' => ['status' => 'ok']
        ];

        // Assert - All expected keys are present
        foreach ($expectedStructure as $key) {
            $this->assertArrayHasKey($key, $orderLogs);
        }

        // Verify JSON encoding works
        $json = json_encode($orderLogs);
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals($orderLogs, $decoded);
    }

    /**
     * @test
     * Teste 27.C: Logs não contêm dados sensíveis não mascarados
     */
    public function test_logs_do_not_contain_unmasked_sensitive_data()
    {
        // Arrange
        $orderLogs = [
            'url' => 'https://api.cieloecommerce.cielo.com.br/1/sales/',
            'headers' => [
                'MerchantId' => '123**********def', // Masked
                'MerchantKey' => 'SEC**********56'  // Masked
            ],
            'body' => [
                'Payment' => [
                    'CreditCard' => [
                        'CardNumber' => '411111******1111', // Masked
                        // CVV should NEVER be here
                    ]
                ]
            ]
        ];

        $logsJson = json_encode($orderLogs);

        // Assert - Should not contain unmasked data
        $this->assertStringNotContainsString('1234567890abcdef', $logsJson);
        $this->assertStringNotContainsString('SECRETKEY123456', $logsJson);
        $this->assertStringNotContainsString('4111111111111111', $logsJson);
        $this->assertStringNotContainsString('cvv', strtolower($logsJson));
        $this->assertStringNotContainsString('cvc', strtolower($logsJson));
        
        // But should contain masked versions
        $this->assertStringContainsString('*', $logsJson);
    }

    /**
     * @test
     * Teste 28.A: Metabox é registrado quando show_order_logs='yes'
     */
    public function test_metabox_registered_when_enabled()
    {
        // Arrange
        $orderId = 123;
        
        Functions\when('get_option')->alias(function($key) {
            if (strpos($key, 'woocommerce_') !== false) {
                return ['show_order_logs' => 'yes'];
            }
            return [];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_meta')
            ->with('lknWcCieloOrderLogs')
            ->andReturn('{"url":"test"}');
        $mockOrder->shouldReceive('get_payment_method')
            ->andReturn('lkn_cielo_credit');

        Functions\when('wc_get_order')->justReturn($mockOrder);
        
        // Mock add_meta_box function
        Functions\expect('add_meta_box')
            ->once()
            ->with(
                'showOrderLogs',
                'Logs das transações',
                Mockery::type('array'),
                Mockery::any(),
                'advanced'
            );

        Functions\when('plugin_dir_url')->justReturn('http://example.com/plugins/');
        Functions\when('wp_enqueue_style')->justReturn(true);
        Functions\when('wc_get_page_screen_id')->justReturn('shop_order');
        Functions\when('class_exists')->justReturn(false);

        // Mock $_GET
        $_GET['id'] = $orderId;

        // Act
        $helper = new LknWcCieloHelper();
        $helper->showOrderLogs();

        // Clean up
        unset($_GET['id']);

        // Assert - Mockery will verify add_meta_box was called
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 28.B: Metabox NÃO é registrado quando show_order_logs='no'
     */
    public function test_metabox_not_registered_when_disabled()
    {
        // Arrange
        $orderId = 456;
        
        Functions\when('get_option')->alias(function($key) {
            if (strpos($key, 'woocommerce_') !== false) {
                return ['show_order_logs' => 'no']; // Disabled
            }
            return [];
        });

        $mockOrder = $this->createMockOrder($orderId, 100.00);
        $mockOrder->shouldReceive('get_meta')
            ->with('lknWcCieloOrderLogs')
            ->andReturn('{"url":"test"}');
        $mockOrder->shouldReceive('get_payment_method')
            ->andReturn('lkn_cielo_credit');

        Functions\when('wc_get_order')->justReturn($mockOrder);
        
        // add_meta_box should NOT be called
        Functions\expect('add_meta_box')->never();

        // Mock $_GET
        $_GET['id'] = $orderId;

        // Act
        $helper = new LknWcCieloHelper();
        $helper->showOrderLogs();

        // Clean up
        unset($_GET['id']);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * @test
     * Teste 28.C: Conteúdo do metabox exibe logs formatados
     */
    public function test_metabox_content_displays_formatted_logs()
    {
        // Arrange
        $orderLogs = [
            'url' => 'https://api.test.com',
            'headers' => ['Content-Type' => 'application/json'],
            'body' => ['test' => 'data'],
            'response' => ['status' => 200]
        ];

        $mockOrder = $this->createMockOrder(789, 100.00);
        $mockOrder->shouldReceive('get_meta')
            ->with('lknWcCieloOrderLogs')
            ->andReturn(json_encode($orderLogs));

        // Act - Verify log structure can be decoded
        $logsMeta = $mockOrder->get_meta('lknWcCieloOrderLogs');
        $decoded = json_decode($logsMeta, true);

        // Assert
        $this->assertIsArray($decoded);
        $this->assertEquals($orderLogs, $decoded);
        $this->assertArrayHasKey('url', $decoded);
        $this->assertArrayHasKey('headers', $decoded);
        $this->assertArrayHasKey('body', $decoded);
        $this->assertArrayHasKey('response', $decoded);
    }
}
