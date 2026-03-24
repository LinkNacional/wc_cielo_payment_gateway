<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Debit;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;
use Brain\Monkey\Actions;
use Mockery;
use Exception;

/**
 * Tests for LknWCGatewayCieloDebit payment processing functionality
 */
class DebitCardProcessingTest extends TestCase
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
        Functions\when('plugin_dir_url')->justReturn('http://localhost/wp-content/plugins/test/');
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
        Functions\when('absint')->returnArg();
        Functions\when('get_query_var')->justReturn(0);
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
        Functions\when('wp_remote_get')->justReturn([
            'body' => json_encode([
                'Payment' => [
                    'Status' => 2,
                    'CapturedAmount' => 10000
                ]
            ])
        ]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('current_user_can')->justReturn(true);
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
        $mockWC->cart->shouldReceive('get_subtotal')->andReturn(100.00);
        $mockWC->cart->shouldReceive('get_shipping_total')->andReturn(10.00);
        $mockWC->cart->shouldReceive('get_total_tax')->andReturn(5.00);
        $mockWC->cart->shouldReceive('get_discount_total')->andReturn(0.00);
        $mockWC->cart->shouldReceive('get_fees')->andReturn([]);
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
        $this->mockOrder->shouldReceive('get_payment_method')->andReturn('lkn_cielo_debit');
        $this->mockOrder->shouldReceive('get_user_id')->andReturn(1);
        $this->mockOrder->shouldReceive('set_transaction_id')->andReturn('');
        $this->mockOrder->shouldReceive('get_transaction_id')->andReturn('test_payment_id');
        $this->mockOrder->shouldReceive('get_subtotal')->andReturn(100.00);
        $this->mockOrder->shouldReceive('get_shipping_total')->andReturn(10.00);
        $this->mockOrder->shouldReceive('get_total_tax')->andReturn(5.00);
        $this->mockOrder->shouldReceive('get_discount_total')->andReturn(0.00);
        $this->mockOrder->shouldReceive('get_fees')->andReturn([]);

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
            ->shouldReceive('getCardProvider')->andReturn('Visa')
            ->shouldReceive('censorString')->returnArg();

        // Create gateway instance - use reflection to inject mock logger
        $this->gateway = new LknWCGatewayCieloDebit();
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
        $this->assertEquals('lkn_cielo_debit', $this->gateway->id);
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

    public function test_validate_fields_with_valid_data()
    {
        // Mock POST data
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        
        $result = $this->gateway->validate_fields();
        $this->assertTrue($result);
    }

    public function test_validate_fields_fails_with_invalid_nonce()
    {
        Functions\when('wp_verify_nonce')->justReturn(false);
        
        $_POST['nonce_lkn_cielo_debit'] = 'invalid_nonce';
        
        $result = $this->gateway->validate_fields();
        $this->assertFalse($result);
    }

    public function test_validate_card_number_with_valid_visa()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_number');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '4532015112830366', false);  // Valid Visa
        $this->assertTrue($result);
    }

    public function test_validate_card_number_with_invalid_card()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_number');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '1234567890123456', false);  // Invalid
        $this->assertFalse($result);
    }

    public function test_validate_card_number_starting_with_zero()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_number');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '0532015112830366', false);  
        $this->assertFalse($result);
    }

    public function test_validate_exp_date_with_valid_date()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_exp_date');
        $reflection->setAccessible(true);
        
        Functions\when('date')->with('Y')->andReturn('2024');
        Functions\when('date')->with('n')->andReturn('3');
        
        $result = $reflection->invoke($this->gateway, '12/25', false);  // Valid future date
        $this->assertTrue($result);
    }

    public function test_validate_exp_date_with_past_date()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_exp_date');
        $reflection->setAccessible(true);
        
        Functions\when('date')->with('Y')->andReturn('2024');
        Functions\when('date')->with('n')->andReturn('3');
        
        $result = $reflection->invoke($this->gateway, '01/23', false);  // Past date
        $this->assertFalse($result);
    }

    public function test_validate_cvv_with_valid_cvv()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_cvv'); 
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '123', false);  // Valid 3-digit CVV
        $this->assertTrue($result);
        
        $result = $reflection->invoke($this->gateway, '1234', false);  // Valid 4-digit CVV
        $this->assertTrue($result);
    }

    public function test_validate_cvv_with_invalid_cvv()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_cvv');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '12', false);  // Too short
        $this->assertFalse($result);
        
        $result = $reflection->invoke($this->gateway, '12345', false);  // Too long
        $this->assertFalse($result);
        
        $result = $reflection->invoke($this->gateway, 'abc', false);  // Non-numeric
        $this->assertFalse($result);
    }

    public function test_process_payment_with_valid_data()
    {
        // Mock POST data
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_cardholder_name'] = 'John Doe';

        // Mock the get_option method to return test values
        $gateway_options = [
            'merchant_id' => 'test_merchant_id',
            'merchant_key' => 'test_merchant_key',
            'env' => 'sandbox',
            'invoiceDesc' => 'Test Order'
        ];
        
        Functions\when('get_option')->justReturn($gateway_options);
        
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
        
        $_POST['nonce_lkn_cielo_debit'] = 'invalid_nonce';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nonce verification failed');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_throws_exception_with_invalid_card()
    {
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '1234567890123456';  // Invalid card
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_cardholder_name'] = 'John Doe';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Debit Card number is invalid');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_throws_exception_with_empty_merchant_id()
    {
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_cardholder_name'] = 'John Doe';
        
        // Mock empty merchant ID
        $reflection = new \ReflectionClass($this->gateway);
        $settingsProperty = $reflection->getProperty('settings');
        $settingsProperty->setAccessible(true);
        $settingsProperty->setValue($this->gateway, ['merchant_id' => '']);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Cielo API 3.0 credentials');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_refund_with_valid_transaction()
    {
        $result = $this->gateway->process_refund(123, 50.00, 'Test refund');
        
        // Should return true for successful refund
        $this->assertTrue($result);
    }

    public function test_process_refund_denies_access_without_permission()
    {
        Functions\when('current_user_can')->justReturn(false);
        
        $result = $this->gateway->process_refund(123, 50.00, 'Test refund');
        
        $this->assertInstanceOf('WP_Error', $result);
    }

    public function test_payment_fields_renders_form_elements()
    {
        ob_start();
        $this->gateway->payment_fields();
        $output = ob_get_clean();
        
        // Should contain form elements for debit card
        $this->assertIsString($output);
        // The method doesn't return HTML directly but enqueues scripts
        // so we just verify it doesn't throw errors
    }

    public function test_get_subtotal_plus_shipping_calculation()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_subtotal_plus_shipping');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway);
        
        // Should return sum of subtotal (100) + shipping (10) = 110
        $this->assertEquals(110.00, $result);
    }

    public function test_get_fees_total_calculation()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_fees_total');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway);
        
        // Should return 0 since no external fees
        $this->assertEquals(0, $result);
    }

    public function test_get_taxes_total_calculation()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_taxes_total');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway);
        
        // Should return tax total (5.00)
        $this->assertEquals(5.00, $result);
    }

    public function test_get_discounts_total_calculation()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'get_discounts_total');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway);
        
        // Should return discount total (0.00)
        $this->assertEquals(0.00, $result);
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

    public function test_lkn_get_cart_total_calculation()
    {
        $result = LknWCGatewayCieloDebit::lknGetCartTotal();
        
        // Should return cart total as string
        $this->assertIsString($result);
    }

    public function test_validate_card_holder_name_with_valid_name()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_holder_name');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, 'John Doe', false);
        $this->assertTrue($result);
    }

    public function test_validate_card_holder_name_with_empty_name()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'validate_card_holder_name');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '', false);
        $this->assertFalse($result);
    }

    public function test_luhn_algorithm_with_valid_card()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'luhnAlgorithmIsValid');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '4532015112830366');  // Valid Visa
        $this->assertTrue($result);
    }

    public function test_luhn_algorithm_with_invalid_card()
    {
        $reflection = new \ReflectionMethod($this->gateway, 'luhnAlgorithmIsValid');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, '1234567890123456');  // Invalid
        $this->assertFalse($result);
    }

    public function test_admin_load_script_enqueues_scripts()
    {
        $_GET['page'] = 'wc-settings';
        $_GET['tab'] = 'checkout';
        $_GET['section'] = 'lkn_cielo_debit';
        
        Functions\expect('wp_enqueue_script')->atLeast()->once();
        Functions\expect('wp_localize_script')->atLeast()->once();
        Functions\expect('wp_enqueue_style')->atLeast()->once();
        
        $this->gateway->admin_load_script();
    }

    public function test_process_payment_handles_api_error()
    {
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_cardholder_name'] = 'John Doe';

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

    public function test_gateway_name_added_to_order_notes()
    {
        $note_data = [
            'comment_content' => '[lkn_cielo_debit] Payment completed successfully'
        ];
        $args = ['order_id' => 123];
        
        $result = $this->gateway->add_gateway_name_to_notes($note_data, $args);
        
        $this->assertStringContainsString('Cielo - Debit Card', $result['comment_content']);
        $this->assertStringNotContainsString('[lkn_cielo_debit]', $result['comment_content']);
    }

    public function test_get_return_url_method()
    {
        Functions\when('get_permalink')->justReturn('http://test.com/order-received/');
        
        $reflection = new \ReflectionMethod($this->gateway, 'get_return_url');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->gateway, $this->mockOrder);
        
        $this->assertIsString($result);
    }

    public function test_currency_conversion_when_needed()
    {
        $this->mockOrder->shouldReceive('get_currency')->andReturn('USD');
        
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_cardholder_name'] = 'John Doe';
        
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

    public function test_handle_wp_error_in_process_payment()
    {
        $_POST['nonce_lkn_cielo_debit'] = 'valid_nonce';
        $_POST['lkn_dc_ccno'] = '4532015112830366';
        $_POST['lkn_dc_expdate'] = '12/25';
        $_POST['lkn_dc_cvc'] = '123';
        $_POST['lkn_dc_cardholder_name'] = 'John Doe';

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
}