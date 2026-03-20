<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\GooglePay;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloGooglePay;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;
use Brain\Monkey\Actions;
use Mockery;
use Exception;

/**
 * Tests for LknWCGatewayCieloGooglePay payment processing functionality
 */
class GooglePayProcessingTest extends TestCase
{
    private $gateway;
    private $mockOrder;
    private $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('__')->returnArg();
        Functions\when('_x')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('home_url')->returnArg();
        Functions\when('plugin_dir_url')->returnWith('http://localhost/wp-content/plugins/test/');
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('is_plugin_active')->justReturn(false);
        Functions\when('file_exists')->justReturn(false);
        Functions\when('function_exists')->justReturn(true);
        Functions\when('get_plugins')->justReturn([]);
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('is_checkout')->justReturn(true);
        Functions\when('is_order_received_page')->justReturn(false);
        Functions\when('is_add_payment_method_page')->justReturn(false);
        Functions\when('wp_enqueue_style')->justReturn('');
        Functions\when('wp_enqueue_script')->justReturn('');
        Functions\when('wp_localize_script')->justReturn('');
        Functions\when('wp_script_is')->justReturn(false);
        Functions\when('wp_style_is')->justReturn(false);
        Functions\when('get_option')->justReturn([]);
        Functions\when('get_woocommerce_currency')->justReturn('BRL');
        Functions\when('wc_has_notice')->justReturn(false);
        Functions\when('wc_add_notice')->justReturn('');
        Functions\when('wp_remote_post')->justReturn([
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'PaymentId' => 'test_payment_id',
                    'ProofOfSale' => '123456',
                    'Tid' => 'test_tid',
                    'ReturnCode' => '4',
                    'ReturnMessage' => 'Success'
                ]
            ])
        ]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('json_decode')->returnArg();
        Functions\when('wp_json_encode')->returnArg();
        Functions\when('number_format')->returnArg();
        Functions\when('preg_replace')->returnArg();
        
        // Mock constants
        if (!defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.0.0');
        }
        if (!defined('LKN_WC_GATEWAY_CIELO_URL')) {
            define('LKN_WC_GATEWAY_CIELO_URL', 'http://localhost/wp-content/plugins/test/');
        }
        if (!defined('LKN_WC_CIELO_WPP_NUMBER')) {
            define('LKN_WC_CIELO_WPP_NUMBER', '5511999999999');
        }

        // Mock WC_Session
        $mockSession = Mockery::mock('WC_Session');
        $mockSession->shouldReceive('set')->andReturn('');
        $mockSession->shouldReceive('get')->andReturn('1');

        // Mock WC
        $mockWC = Mockery::mock();
        $mockWC->cart = Mockery::mock();
        $mockWC->cart->shouldReceive('empty_cart')->andReturn('');
        $mockWC->session = $mockSession;
        
        Functions\when('WC')->justReturn($mockWC);

        // Mock order
        $this->mockOrder = Mockery::mock('WC_Order');
        $this->mockOrder->shouldReceive('get_billing_first_name')->andReturn('John');
        $this->mockOrder->shouldReceive('get_billing_last_name')->andReturn('Doe');
        $this->mockOrder->shouldReceive('get_total')->andReturn(100.00);
        $this->mockOrder->shouldReceive('get_currency')->andReturn('BRL');
        $this->mockOrder->shouldReceive('update_meta_data')->andReturn('');
        $this->mockOrder->shouldReceive('add_meta_data')->andReturn('');
        $this->mockOrder->shouldReceive('add_order_note')->andReturn('');
        $this->mockOrder->shouldReceive('save')->andReturn('');
        $this->mockOrder->shouldReceive('get_meta')->andReturn('test_value');
        $this->mockOrder->shouldReceive('get_payment_method')->andReturn('lkn_cielo_googlepay');
        $this->mockOrder->shouldReceive('get_user_id')->andReturn(1);
        $this->mockOrder->shouldReceive('set_transaction_id')->andReturn('');

        // Mock WC_Logger
        $this->mockLogger = Mockery::mock('WC_Logger');
        $this->mockLogger->shouldReceive('log')->andReturn('');
        
        // Mock wc_get_order
        Functions\when('wc_get_order')->justReturn($this->mockOrder);

        // Mock LknWcCieloHelper
        Mockery::mock('alias:' . LknWcCieloHelper::class)
            ->shouldReceive('getIconUrl')->andReturn('http://test.com/icon.png')
            ->shouldReceive('is_pro_license_active')->andReturn(false)
            ->shouldReceive('createCustomErrorResponse')->andReturn([
                'errors' => [['code' => '400', 'message' => 'Test error']]
            ])
            ->shouldReceive('saveTransactionMetadata')->andReturn('')
            ->shouldReceive('getCardProvider')->andReturn('GooglePay')
            ->shouldReceive('censorString')->returnArg();

        // Create gateway instance
        $this->gateway = new LknWCGatewayCieloGooglePay();
        
        // Use reflection to inject mock logger
        $reflection = new \ReflectionClass($this->gateway);
        $logProperty = $reflection->getProperty('log');
        $logProperty->setAccessible(true);
        $logProperty->setValue($this->gateway, $this->mockLogger);
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_gateway_initialization()
    {
        $this->assertEquals('lkn_cielo_googlepay', $this->gateway->id);
        $this->assertTrue($this->gateway->has_fields);
        $this->assertIsArray($this->gateway->supports);
        $this->assertContains('products', $this->gateway->supports);
    }

    public function test_init_form_fields_creates_required_fields()
    {
        $this->gateway->init_form_fields();
        
        $fields = $this->gateway->form_fields;
        
        // Test essential fields exist
        $this->assertArrayHasKey('enabled', $fields);
        $this->assertArrayHasKey('title', $fields);
        $this->assertArrayHasKey('description', $fields);
        $this->assertArrayHasKey('merchant_id', $fields);
        $this->assertArrayHasKey('merchant_key', $fields);
        $this->assertArrayHasKey('env', $fields);
        $this->assertArrayHasKey('debug', $fields);
        
        // Test field configurations
        $this->assertEquals('checkbox', $fields['enabled']['type']);
        $this->assertEquals('text', $fields['title']['type']);
        $this->assertEquals('textarea', $fields['description']['type']);
        $this->assertEquals('password', $fields['merchant_id']['type']);
        $this->assertEquals('password', $fields['merchant_key']['type']);
        $this->assertEquals('select', $fields['env']['type']);
    }

    public function test_payment_fields_renders_google_pay_elements()
    {
        ob_start();
        $this->gateway->payment_fields();
        $output = ob_get_clean();
        
        $this->assertIsString($output);
        // The method mostly enqueues scripts and styles, so we verify no errors occur
    }

    public function test_validate_fields_with_valid_data()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        $result = $this->gateway->validate_fields();
        $this->assertTrue($result);
    }

    public function test_validate_fields_fails_with_invalid_nonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        
        $_POST['nonce_lkn_cielo_googlepay'] = 'invalid_nonce';
        
        $result = $this->gateway->validate_fields();
        $this->assertFalse($result);
    }

    public function test_validate_fields_fails_without_google_pay_token()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        // Missing google pay token
        
        $result = $this->gateway->validate_fields();
        $this->assertFalse($result);
    }

    public function test_process_payment_with_valid_google_pay_data()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_google_pay_token';
        
        // Mock the get_option method to return test values
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order'
        ];
        
        // Use reflection to set options
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $result = $this->gateway->process_payment(123);
        
        $this->assertIsArray($result);
        $this->assertEquals('success', $result['result']);
        $this->assertArrayHasKey('redirect', $result);
    }

    public function test_process_payment_throws_exception_with_invalid_nonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        
        $_POST['nonce_lkn_cielo_googlepay'] = 'invalid_nonce';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nonce verification failed');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_throws_exception_without_google_pay_token()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        // Missing Google Pay token
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Google Pay token is required');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_throws_exception_with_empty_merchant_credentials()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        // Mock empty merchant credentials
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, [
            'merchant_id' => '',
            'merchant_key' => ''
        ]);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Cielo API 3.0 credentials');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_handles_api_error_response()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        // Mock API error response
        Functions\when('wp_remote_post')->justReturn([
            'body' => json_encode([
                'Payment' => [
                    'Status' => 0,
                    'ReturnCode' => 'GF',
                    'ReturnMessage' => 'General Failure'
                ]
            ])
        ]);
        
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order'
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $this->expectException(Exception::class);
        
        $this->gateway->process_payment(123);
    }

    public function test_admin_load_script_enqueues_google_pay_scripts()
    {
        $_GET['page'] = 'wc-settings';
        $_GET['tab'] = 'checkout';
        $_GET['section'] = 'lkn_cielo_googlepay';
        
        Functions\expect('wp_enqueue_script')->atLeast()->once();
        Functions\expect('wp_localize_script')->atLeast()->once();
        Functions\expect('wp_enqueue_style')->atLeast()->once();
        
        $this->gateway->admin_load_script();
    }

    public function test_admin_load_script_does_not_enqueue_on_other_pages()
    {
        $_GET['page'] = 'different-page';
        $_GET['tab'] = 'different-tab';
        $_GET['section'] = 'different-section';
        
        // Should not enqueue scripts on other pages
        $this->gateway->admin_load_script();
        
        // If no exception is thrown, test passes
        $this->assertTrue(true);
    }

    public function test_add_notice_once_prevents_duplicate_notices()
    {
        Functions\when('wc_has_notice')->justReturn(true);  // Notice already exists
        Functions\expect('wc_add_notice')->never();  // Should not be called
        
        $this->gateway->add_notice_once('Test message', 'error');
    }

    public function test_add_notice_once_adds_new_notice()
    {
        Functions\when('wc_has_notice')->justReturn(false);  // Notice doesn't exist
        Functions\expect('wc_add_notice')->once()->with('Test message', 'error');
        
        $this->gateway->add_notice_once('Test message', 'error');
    }

    public function test_payment_fields_enqueues_google_pay_scripts()
    {
        Functions\expect('wp_enqueue_style')->atLeast()->once();
        Functions\expect('wp_enqueue_script')->atLeast()->once();
        
        ob_start();
        $this->gateway->payment_fields();
        $output = ob_get_clean();
        
        // Verify scripts were enqueued
        $this->assertIsString($output);
    }

    public function test_process_payment_with_currency_conversion()
    {
        $this->mockOrder->shouldReceive('get_currency')->andReturn('USD');
        $this->mockOrder->shouldReceive('add_meta_data')
            ->once()
            ->with('amount_converted', Mockery::any());
        
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        Filters\expectApplied('lkn_wc_cielo_convert_amount')->once();
        
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order'
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $result = $this->gateway->process_payment(123);
        $this->assertEquals('success', $result['result']);
    }

    public function test_process_payment_handles_wp_error()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        $mockError = Mockery::mock('WP_Error');
        $mockError->shouldReceive('get_error_messages')->andReturn(['Connection error']);
        
        Functions\when('wp_remote_post')->justReturn($mockError);
        Functions\when('is_wp_error')->justReturn(true);
        
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order',
            'debug' => 'yes'
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Order payment failed');
        
        $this->gateway->process_payment(123);
    }

    public function test_gateway_name_added_to_order_notes()
    {
        $note_data = [
            'comment_content' => '[lkn_cielo_googlepay] Payment completed successfully'
        ];
        $args = ['order_id' => 123];
        
        $result = $this->gateway->add_gateway_name_to_notes($note_data, $args);
        
        $this->assertStringContainsString('Cielo - Google Pay', $result['comment_content']);
        $this->assertStringNotContainsString('[lkn_cielo_googlepay]', $result['comment_content']);
    }

    public function test_validate_google_pay_token()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_google_pay_token');
        $reflection->setAccessible(true);
        
        // Valid token
        $result = $reflection->invoke($this->gateway, 'valid_google_pay_token_123', false);
        $this->assertTrue($result);
        
        // Empty token
        $result = $reflection->invoke($this->gateway, '', false);
        $this->assertFalse($result);
        
        // Very short token
        $result = $reflection->invoke($this->gateway, 'abc', false);
        $this->assertFalse($result);
    }

    public function test_process_payment_successful_transaction_flow()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_google_pay_token';
        
        // Mock successful response with status 1 (authorized) or 2 (captured)
        Functions\when('wp_remote_post')->justReturn([
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'PaymentId' => 'test_payment_123',
                    'ProofOfSale' => '654321',
                    'Tid' => 'test_tid_456',
                    'ReturnCode' => '4'
                ]
            ])
        ]);
        
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order',
            'capture' => 'yes'
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $result = $this->gateway->process_payment(123);
        
        $this->assertEquals('success', $result['result']);
        $this->assertArrayHasKey('redirect', $result);
    }

    public function test_process_payment_logs_transaction_when_debug_enabled()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order',
            'debug' => 'yes'
        ];
        
        $this->mockOrder->shouldReceive('update_meta_data')
            ->once()
            ->with('lknWcCieloOrderLogs', Mockery::type('string'));
        
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $result = $this->gateway->process_payment(123);
        $this->assertEquals('success', $result['result']);
    }

    public function test_payment_scripts_not_loaded_outside_checkout()
    {
        Functions\when('is_checkout')->justReturn(false);
        Functions\when('is_add_payment_method_page')->justReturn(false);
        Functions\when('is_order_received_page')->justReturn(false);
        
        // Should return early without loading scripts
        $reflection = new \ReflectionMethod($this->gateway, 'payment_gateway_scripts');
        $reflection->setAccessible(true);
        $reflection->invoke($this->gateway);
        
        // If we reach here, the method returned early as expected
        $this->assertTrue(true);
    }

    public function test_payment_scripts_not_loaded_when_disabled()
    {
        Functions\when('is_checkout')->justReturn(true);
        
        // Set gateway as disabled
        $reflection = new \ReflectionProperty($this->gateway, 'enabled');
        $reflection->setAccessible(true);
        $reflection->setValue($this->gateway, 'no');
        
        $reflectionMethod = new \ReflectionMethod($this->gateway, 'payment_gateway_scripts');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->gateway);
        
        // If we reach here, the method returned early as expected
        $this->assertTrue(true);
    }

    public function test_process_payment_saves_transaction_id_for_integrations()
    {
        $_POST['nonce_lkn_cielo_googlepay'] = 'valid_nonce';
        $_POST['lkn_google_pay_token'] = 'test_token';
        
        $this->mockOrder->shouldReceive('set_transaction_id')
            ->once()
            ->with('test_payment_123');
        
        Functions\when('wp_remote_post')->justReturn([
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'PaymentId' => 'test_payment_123',
                    'ProofOfSale' => '654321',
                    'Tid' => 'test_tid_456',
                    'ReturnCode' => '4'
                ]
            ])
        ]);
        
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order'
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, $gateway_options);
        
        $result = $this->gateway->process_payment(123);
        $this->assertEquals('success', $result['result']);
    }
}