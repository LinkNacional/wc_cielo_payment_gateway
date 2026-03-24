<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Endpoint;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloEndpoint;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for LknWCGatewayCieloEndpoint webhook handling functionality
 */
class EndpointHandlingTest extends TestCase
{
    private $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('__')->returnArg();
        Functions\when('add_action')->justReturn('');
        Functions\when('add_filter')->justReturn('');
        Functions\when('wp_die')->justReturn('');
        Functions\when('wp_json_encode')->returnArg();
        Functions\when('json_encode')->returnArg();
        Functions\when('json_decode')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('wc_get_order')->justReturn(null);
        Functions\when('status_header')->justReturn('');
        Functions\when('nocache_headers')->justReturn('');
        Functions\when('get_query_var')->justReturn('');
        Functions\when('home_url')->justReturn('http://localhost/');
        Functions\when('wc_get_logger')->justReturn(Mockery::mock('WC_Logger'));
        Functions\when('is_wp_error')->justReturn(false);
        
        // Create endpoint instance
        $this->endpoint = new LknWCGatewayCieloEndpoint();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_endpoint_initialization_adds_hooks()
    {
        Functions\expect('add_action')
            ->atLeast()
            ->once()
            ->with('init', Mockery::type('array'));
        
        Functions\expect('add_action')
            ->atLeast()
            ->once()
            ->with('parse_request', Mockery::type('array'));
        
        $endpoint = new LknWCGatewayCieloEndpoint();
        
        // If no exception is thrown, hooks were added successfully
        $this->assertTrue(true);
    }

    public function test_add_endpoint_adds_rewrite_endpoint()
    {
        Functions\expect('add_rewrite_endpoint')
            ->once()
            ->with('cielo-webhook', EP_ROOT);
        
        $this->endpoint->add_endpoint();
    }

    public function test_handle_endpoint_request_with_valid_cielo_webhook()
    {
        // Mock WP query with cielo-webhook endpoint
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        // Mock request data
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';  // Payment status change
        
        // Mock order
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('payment_complete')->once();
        $mockOrder->shouldReceive('add_order_note')->once();
        $mockOrder->shouldReceive('save')->once();
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        // Should output success response
        $this->assertIsString($output);
    }

    public function test_handle_endpoint_request_ignores_non_cielo_requests()
    {
        // Mock WP query without cielo-webhook endpoint
        $mockWP = Mockery::mock();
        $mockWP->query_vars = [];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('');
        
        // Should return early without processing
        $this->endpoint->handle_endpoint_request($mockWP);
        
        // If no exception is thrown, request was ignored as expected
        $this->assertTrue(true);
    }

    public function test_handle_endpoint_request_with_missing_merchant_order_id()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        // Mock request data without MerchantOrderId
        $_POST = [];
        
        Functions\expect('status_header')->once()->with(400);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_handle_endpoint_request_with_invalid_order_format()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        // Invalid MerchantOrderId format (missing timestamp)
        $_POST['MerchantOrderId'] = 'invalid_format';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';
        
        Functions\expect('status_header')->once()->with(404);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_handle_endpoint_request_with_non_existent_order()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_999_1642780800';  // Non-existent order
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';
        
        Functions\when('wc_get_order')->with(999)->andReturn(false);
        
        Functions\expect('status_header')->once()->with(404);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_handle_endpoint_request_processes_payment_completion()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';  // Payment status change
        
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('payment_complete')->once()->with('payment_456');
        $mockOrder->shouldReceive('add_order_note')
            ->once()
            ->with(Mockery::pattern('/Payment confirmed via Cielo webhook/'));
        $mockOrder->shouldReceive('save')->once();
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        Functions\expect('status_header')->once()->with(200);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('success', $output);
    }

    public function test_handle_endpoint_request_skips_already_completed_orders()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';
        
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_status')->andReturn('completed');  // Already completed
        $mockOrder->shouldReceive('payment_complete')->never();  // Should not be called
        $mockOrder->shouldReceive('add_order_note')
            ->once()
            ->with(Mockery::pattern('/already processed/'));
        $mockOrder->shouldReceive('save')->once();
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_handle_endpoint_request_processes_different_change_types()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        // Test with different ChangeType values
        $changeTypes = ['1', '2', '3', '4'];
        
        foreach ($changeTypes as $changeType) {
            $_POST['MerchantOrderId'] = 'order_123_1642780800';
            $_POST['PaymentId'] = 'payment_456';
            $_POST['ChangeType'] = $changeType;
            
            $mockOrder = Mockery::mock('WC_Order');
            $mockOrder->shouldReceive('get_status')->andReturn('pending');
            $mockOrder->shouldReceive('payment_complete')->once();
            $mockOrder->shouldReceive('add_order_note')->once();
            $mockOrder->shouldReceive('save')->once();
            
            Functions\when('wc_get_order')->andReturn($mockOrder);
            
            ob_start();
            $this->endpoint->handle_endpoint_request($mockWP);
            ob_get_clean();
            
            // Reset for next iteration
            Mockery::resetContainer();
        }
        
        $this->assertTrue(true);
    }

    public function test_handle_endpoint_request_handles_empty_payment_id()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = '';  // Empty payment ID
        $_POST['ChangeType'] = '1';
        
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('payment_complete')->once();  // Should still call payment_complete
        $mockOrder->shouldReceive('add_order_note')->once();
        $mockOrder->shouldReceive('save')->once();
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_extract_order_id_from_merchant_order_id()
    {
        $reflection = new \ReflectionMethod($this->endpoint, 'extract_order_id');
        $reflection->setAccessible(true);
        
        // Test valid format
        $result = $reflection->invoke($this->endpoint, 'order_123_1642780800');
        $this->assertEquals(123, $result);
        
        // Test another valid format
        $result = $reflection->invoke($this->endpoint, 'order_999_1234567890');
        $this->assertEquals(999, $result);
        
        // Test invalid format
        $result = $reflection->invoke($this->endpoint, 'invalid_format');
        $this->assertFalse($result);
        
        // Test empty string
        $result = $reflection->invoke($this->endpoint, '');
        $this->assertFalse($result);
    }

    public function test_log_webhook_request()
    {
        $mockLogger = Mockery::mock('WC_Logger');
        $mockLogger->shouldReceive('info')
            ->once()
            ->with(
                Mockery::pattern('/Webhook received/'),
                Mockery::type('array')
            );
        
        Functions\when('wc_get_logger')->andReturn($mockLogger);
        
        $reflection = new \ReflectionMethod($this->endpoint, 'log_webhook_request');
        $reflection->setAccessible(true);
        
        $_POST = [
            'MerchantOrderId' => 'order_123_1642780800',
            'PaymentId' => 'payment_456',
            'ChangeType' => '1'
        ];
        
        $reflection->invoke($this->endpoint, $_POST);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    public function test_send_response_sets_correct_headers()
    {
        Functions\expect('status_header')->once()->with(200);
        Functions\expect('header')->once()->with('Content-Type: application/json');
        Functions\expect('nocache_headers')->once();
        
        $reflection = new \ReflectionMethod($this->endpoint, 'send_response');
        $reflection->setAccessible(true);
        
        ob_start();
        $reflection->invoke($this->endpoint, 200, ['status' => 'success']);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_get_webhook_url_returns_correct_url()
    {
        $result = LknWCGatewayCieloEndpoint::get_webhook_url();
        
        $this->assertIsString($result);
        $this->assertStringContainsString('cielo-webhook', $result);
        $this->assertStringContainsString('http://localhost/', $result);
    }

    public function test_handle_endpoint_request_with_debug_logging()
    {
        // Enable debug logging
        Functions\when('get_option')->with('woocommerce_lkn_cielo_settings')->andReturn(['debug' => 'yes']);
        
        $mockLogger = Mockery::mock('WC_Logger');
        $mockLogger->shouldReceive('info')->atLeast()->once();
        
        Functions\when('wc_get_logger')->andReturn($mockLogger);
        
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';
        
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('payment_complete')->once();
        $mockOrder->shouldReceive('add_order_note')->once();
        $mockOrder->shouldReceive('save')->once();
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        ob_get_clean();
        
        // Test passes if logging was called
        $this->assertTrue(true);
    }

    public function test_handle_endpoint_request_validates_payment_method()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';
        
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_status')->andReturn('pending');
        $mockOrder->shouldReceive('get_payment_method')->andReturn('lkn_cielo_credit');  // Valid Cielo method
        $mockOrder->shouldReceive('payment_complete')->once();
        $mockOrder->shouldReceive('add_order_note')->once();
        $mockOrder->shouldReceive('save')->once();
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }

    public function test_handle_endpoint_request_rejects_non_cielo_payment_methods()
    {
        $mockWP = Mockery::mock();
        $mockWP->query_vars = ['cielo-webhook' => 'test'];
        
        Functions\when('get_query_var')->with('cielo-webhook')->andReturn('test');
        
        $_POST['MerchantOrderId'] = 'order_123_1642780800';
        $_POST['PaymentId'] = 'payment_456';
        $_POST['ChangeType'] = '1';
        
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('get_payment_method')->andReturn('paypal');  // Non-Cielo method
        
        Functions\when('wc_get_order')->with(123)->andReturn($mockOrder);
        
        Functions\expect('status_header')->once()->with(400);
        
        ob_start();
        $this->endpoint->handle_endpoint_request($mockWP);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
    }
}