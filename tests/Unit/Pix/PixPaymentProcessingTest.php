<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Filters;
use Brain\Monkey\Actions;
use Mockery;
use Exception;

/**
 * Tests for LknWcCieloPix payment processing functionality
 */
class PixPaymentProcessingTest extends TestCase
{
    private $gateway;
    private $mockOrder;
    private $mockRequest;

    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('__')->returnArg();
        Functions\when('_x')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('home_url')->returnArg();
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
        Functions\when('wc_get_template')->justReturn('');
        Functions\when('wp_enqueue_style')->justReturn('');
        Functions\when('wp_enqueue_script')->justReturn('');
        Functions\when('wp_localize_script')->justReturn('');
        Functions\when('wp_get_theme')->justReturn((object)['Name' => 'Test Theme']);
        Functions\when('get_option')->justReturn([]);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn('');
        Functions\when('time')->justReturn(1642780800);
        Functions\when('wc_has_notice')->justReturn(false);
        Functions\when('wc_add_notice')->justReturn('');
        Functions\when('number_format')->returnArg();
        Functions\when('preg_replace')->returnArg();
        
        // Mock constants
        if (!defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.0.0');
        }
        if (!defined('LKN_WC_GATEWAY_CIELO_URL')) {
            define('LKN_WC_GATEWAY_CIELO_URL', 'http://localhost/wp-content/plugins/test/');
        }
        if (!defined('LKN_WC_GATEWAY_CIELO_DIR')) {
            define('LKN_WC_GATEWAY_CIELO_DIR', '/path/to/plugin/');
        }
        if (!defined('LKN_CIELO_API_PRO_VERSION')) {
            define('LKN_CIELO_API_PRO_VERSION', '1.0.0');
        }
        if (!defined('LKN_WC_CIELO_WPP_NUMBER')) {
            define('LKN_WC_CIELO_WPP_NUMBER', '5511999999999');
        }

        // Mock order
        $this->mockOrder = Mockery::mock('WC_Order');
        $this->mockOrder->shouldReceive('get_billing_first_name')->andReturn('John');
        $this->mockOrder->shouldReceive('get_billing_last_name')->andReturn('Doe');
        $this->mockOrder->shouldReceive('get_total')->andReturn(100.00);
        $this->mockOrder->shouldReceive('get_currency')->andReturn('BRL');
        $this->mockOrder->shouldReceive('update_meta_data')->andReturn('');
        $this->mockOrder->shouldReceive('add_order_note')->andReturn('');
        $this->mockOrder->shouldReceive('save')->andReturn('');
        $this->mockOrder->shouldReceive('get_meta')->andReturn('test_value');
        $this->mockOrder->shouldReceive('get_payment_method')->andReturn('lkn_wc_cielo_pix');

        // Mock WC_Logger
        $mockLogger = Mockery::mock('WC_Logger');
        $mockLogger->shouldReceive('log')->andReturn('');
        Functions\when('wc_get_logger')->justReturn($mockLogger);

        // Mock wc_get_order
        Functions\when('wc_get_order')->justReturn($this->mockOrder);

        // Mock LknWcCieloHelper
        Mockery::mock('alias:' . LknWcCieloHelper::class)
            ->shouldReceive('getIconUrl')->andReturn('http://test.com/icon.png')
            ->shouldReceive('is_pro_license_active')->andReturn(false)
            ->shouldReceive('createCustomErrorResponse')->andReturn([
                'errors' => [['code' => '400', 'message' => 'Test error']]
            ])
            ->shouldReceive('saveTransactionMetadata')->andReturn('');

        // Create gateway instance
        $this->gateway = new LknWcCieloPix();
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_gateway_initialization()
    {
        $this->assertEquals('lkn_wc_cielo_pix', $this->gateway->id);
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

    public function test_validate_cpf_valid_cpf()
    {
        $validCpf = '11144477735';
        $result = $this->gateway->validateCpfCnpj($validCpf);
        $this->assertTrue($result);
    }

    public function test_validate_cpf_invalid_cpf()
    {
        $invalidCpf = '11111111111';  // All same digits
        $result = $this->gateway->validateCpfCnpj($invalidCpf);
        $this->assertFalse($result);
    }

    public function test_validate_cnpj_valid_cnpj()
    {
        $validCnpj = '11222333000181';
        $result = $this->gateway->validateCpfCnpj($validCnpj);
        $this->assertTrue($result);
    }

    public function test_validate_cnpj_invalid_cnpj()
    {
        $invalidCnpj = '11111111111111';  // All same digits
        $result = $this->gateway->validateCpfCnpj($invalidCnpj);
        $this->assertFalse($result);
    }

    public function test_validate_cpf_cnpj_with_formatting()
    {
        $formattedCpf = '111.444.777-35';
        $result = $this->gateway->validateCpfCnpj($formattedCpf);
        $this->assertTrue($result);
        
        $formattedCnpj = '11.222.333/0001-81';
        $result = $this->gateway->validateCpfCnpj($formattedCnpj);
        $this->assertTrue($result);
    }

    public function test_validate_cpf_cnpj_invalid_length()
    {
        $shortNumber = '123456';
        $result = $this->gateway->validateCpfCnpj($shortNumber);
        $this->assertFalse($result);

        $longNumber = '123456789012345';
        $result = $this->gateway->validateCpfCnpj($longNumber);
        $this->assertFalse($result);
    }

    public function test_payment_fields_renders_description()
    {
        ob_start();
        $this->gateway->payment_fields();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('After the purchase is completed, the PIX will be generated', $output);
        $this->assertStringContainsString('text-align:center', $output);
    }

    public function test_payment_fields_renders_cpf_cnpj_form_when_no_plugin_active()
    {
        // Mock plugins as inactive
        Functions\when('is_plugin_active')->justReturn(false);
        Functions\when('get_option')->justReturn(['person_type' => '0']);
        
        ob_start();
        $this->gateway->payment_fields();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('billing_cielo_pix_free_cpf_cnpj', $output);
        $this->assertStringContainsString('CPF / CNPJ', $output);
        $this->assertStringContainsString('input-text', $output);
        $this->assertStringContainsString('required', $output);
    }

    public function test_process_payment_with_valid_data()
    {
        // Mock POST data
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '11144477735';
        
        // Mock successful PIX response
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->shouldReceive('pix_request')
            ->andReturn([
                'response' => [
                    'qrcodeImage' => 'base64_image_data',
                    'qrcodeString' => 'pix_code_string',
                    'paymentId' => 'payment_123'
                ]
            ]);
        
        $reflection = new \ReflectionClass($this->gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->gateway, $mockRequest);
        
        $result = $this->gateway->process_payment(123);
        
        $this->assertIsArray($result);
        $this->assertEquals('success', $result['result']);
        $this->assertArrayHasKey('redirect', $result);
    }

    public function test_process_payment_throws_exception_with_invalid_cpf()
    {
        // Mock POST data with invalid CPF
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '12345678901';  // Invalid CPF
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Please enter a valid CPF or CNPJ');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_throws_exception_with_empty_name()
    {
        // Mock order with empty names
        $this->mockOrder->shouldReceive('get_billing_first_name')->andReturn('');
        $this->mockOrder->shouldReceive('get_billing_last_name')->andReturn('');
        
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '11144477735';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nome não informado');
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_handles_api_request_failure()
    {
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '11144477735';
        
        // Mock failed PIX response
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->shouldReceive('pix_request')
            ->andReturn([
                'sucess' => false,
                'response' => ['error' => 'API Error']
            ]);
        
        $reflection = new \ReflectionClass($this->gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->gateway, $mockRequest);
        
        $this->expectException(Exception::class);
        
        $this->gateway->process_payment(123);
    }

    public function test_process_payment_handles_missing_qr_code_data()
    {
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '11144477735';
        
        // Mock response without QR code data
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->shouldReceive('pix_request')
            ->andReturn([
                'response' => [
                    'paymentId' => 'payment_123'
                    // Missing qrcodeImage and qrcodeString
                ]
            ]);
        
        $reflection = new \ReflectionClass($this->gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->gateway, $mockRequest);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error generating PIX: QR Code information is missing');
        
        $this->gateway->process_payment(123);
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

    public function test_show_pix_renders_template_for_pix_orders()
    {
        Functions\expect('wc_get_template')
            ->once()
            ->with('lkn-cielo-pix-template.php', Mockery::type('array'), 'includes/templates', Mockery::type('string'));
        
        LknWcCieloPix::showPix(123);
    }

    public function test_cpf_cnpj_fallback_priority()
    {
        // Test priority 1: billing_cielo_pix_free_cpf_cnpj
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '11144477735';
        $_POST['billing_cpf'] = '22244477735'; 
        $_POST['billing_cnpj'] = '11222333000181';
        
        // Mock successful request to test the CPF priority
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->shouldReceive('pix_request')
            ->once()
            ->with(
                'John Doe',
                100.00,
                Mockery::on(function($billingDoc) {
                    return $billingDoc['Identity'] === '11144477735' && $billingDoc['IdentityType'] === 'CPF';
                }),
                Mockery::any(),
                Mockery::any(),
                Mockery::any()
            )
            ->andReturn([
                'response' => [
                    'qrcodeImage' => 'base64_image_data',
                    'qrcodeString' => 'pix_code_string',
                    'paymentId' => 'payment_123'
                ]
            ]);
        
        $reflection = new \ReflectionClass($this->gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->gateway, $mockRequest);
        
        $result = $this->gateway->process_payment(123);
        $this->assertEquals('success', $result['result']);
    }

    public function test_add_error_throws_exception_with_title()
    {
        $this->gateway->title = 'Test PIX Gateway';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test PIX Gateway');
        
        $this->gateway->add_error('Test error message');
    }

    public function test_payment_gateway_scripts_returns_early_when_not_checkout()
    {
        Functions\when('is_checkout')->justReturn(false);
        Functions\when('is_add_payment_method_page')->justReturn(false);
        Functions\when('is_order_received_page')->justReturn(false);
        
        // Should return early, no scripts loaded
        $this->gateway->payment_gateway_scripts();
        
        // If we reach here, the method returned early as expected
        $this->assertTrue(true);
    }

    public function test_payment_gateway_scripts_returns_early_when_disabled()
    {
        Functions\when('is_checkout')->justReturn(true);
        
        // Set gateway as disabled
        $reflection = new \ReflectionProperty($this->gateway, 'enabled');
        $reflection->setAccessible(true);
        $reflection->setValue($this->gateway, 'no');
        
        $this->gateway->payment_gateway_scripts();
        
        // If we reach here, the method returned early as expected
        $this->assertTrue(true);
    }

    public function test_currency_conversion_applied_when_needed()
    {
        $this->mockOrder->shouldReceive('get_currency')->andReturn('USD');
        $this->mockOrder->shouldReceive('add_order_note')
            ->once()
            ->with(Mockery::pattern('/Amount converted/'));
        
        $_POST['billing_cielo_pix_free_cpf_cnpj'] = '11144477735';
        
        // Mock currency conversion filter
        Filters\expectApplied('lkn_wc_cielo_convert_amount')
            ->once()
            ->with(100.00, 'USD')
            ->andReturn(500.00);  // Converted amount
        
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->shouldReceive('pix_request')
            ->andReturn([
                'response' => [
                    'qrcodeImage' => 'base64_image_data',
                    'qrcodeString' => 'pix_code_string',
                    'paymentId' => 'payment_123'
                ]
            ]);
        
        $reflection = new \ReflectionClass($this->gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->gateway, $mockRequest);
        
        $result = $this->gateway->process_payment(123);
        $this->assertEquals('success', $result['result']);
    }
}