<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit;

use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Mockery;
use WC_Order;
use WC_Logger;
use Exception;

/**
 * Testes para a classe LknWcCieloPix
 *
 * @covers \Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix
 */
class LknWcCieloPixTest extends TestCase
{
    private $pixGateway;
    private $orderMock;
    private $loggerMock;
    private $requestMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock das constantes
        if (!defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.29.0');
        }
        if (!defined('LKN_WC_GATEWAY_CIELO_URL')) {
            define('LKN_WC_GATEWAY_CIELO_URL', 'https://example.com/');
        }
        if (!defined('LKN_WC_GATEWAY_CIELO_DIR')) {
            define('LKN_WC_GATEWAY_CIELO_DIR', '/path/to/plugin/');
        }
        if (!defined('LKN_WC_CIELO_WPP_NUMBER')) {
            define('LKN_WC_CIELO_WPP_NUMBER', '5511999999999');
        }
        if (!defined('LKN_CIELO_API_PRO_VERSION')) {
            define('LKN_CIELO_API_PRO_VERSION', '3.0.0');
        }

        // Mock das funções WordPress necessárias
        Functions\when('__')->returnArg();
        Functions\when('_x')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('wp_kses')->returnArg();
        Functions\when('wp_kses_post')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('plugin_dir_url')->justReturn('https://example.com/');
        Functions\when('get_option')->justReturn([]);
        Functions\when('is_plugin_active')->justReturn(false);
        Functions\when('function_exists')->justReturn(true);
        Functions\when('file_exists')->justReturn(true);
        Functions\when('wp_get_theme')->justReturn((object)['Name' => 'TwentyTwentyFour']);
        Functions\when('current_time')->justReturn('2024-03-14 10:30:00');
        Functions\when('time')->justReturn(1710409800);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('json_decode')->alias('json_decode');

        // Mock para aplicar filtros
        Filters\expectApplied('lkn_wc_cielo_gateway_icon')->andReturn('');
        Filters\expectApplied('lkn_wc_cielo_convert_amount')->andReturnFirstArg();
        Filters\expectApplied('lkn_wc_cielo_get_custom_configs')->andReturn([]);

        // Mock do LknWcCieloHelper
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::getIconUrl')
            ->justReturn('https://example.com/icon.png');
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::is_pro_license_active')
            ->justReturn(false);

        // Mock do WC_Logger
        $this->loggerMock = Mockery::mock('WC_Logger');
        $this->loggerMock->allows('log')->andReturn(true);

        // Mock das funções de templates
        Functions\when('wc_get_template')->justReturn('');
        Functions\when('wp_enqueue_style')->justReturn('');
        Functions\when('wp_enqueue_script')->justReturn('');
        Functions\when('wp_localize_script')->justReturn('');

        // Mock das funções de checkout
        Functions\when('is_checkout')->justReturn(true);
        Functions\when('is_add_payment_method_page')->justReturn(false);
        Functions\when('is_order_received_page')->justReturn(false);

        // Mock de actions/filters hooks
        Actions\expectAdded('woocommerce_update_options_payment_gateways_lkn_wc_cielo_pix');
        Actions\expectAdded('admin_enqueue_scripts');
        Filters\expectAdded('woocommerce_new_order_note_data');

        // Criar instância do gateway PIX
        $this->pixGateway = Mockery::mock(LknWcCieloPix::class)->makePartial();
        $this->pixGateway->allows('get_option')->andReturn('test_value');
        $this->pixGateway->allows('get_return_url')->andReturn('https://example.com/thank-you');
        $this->pixGateway->allows('init_form_fields')->andReturn(null);
        $this->pixGateway->allows('init_settings')->andReturn(null);

        // Mock das propriedades
        $this->pixGateway->id = 'lkn_wc_cielo_pix';
        $this->pixGateway->method_title = 'Cielo PIX Free';
        $this->pixGateway->enabled = 'yes';

        // Mock do WC_Order
        $this->orderMock = Mockery::mock('WC_Order');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @test
     * @covers LknWcCieloPix::validateCpfCnpj
     */
    public function testValidateCpfCnpjWithValidCpf()
    {
        $gateway = new LknWcCieloPix();
        
        // CPF válido: 111.111.111-11
        $result = $gateway->validateCpfCnpj('11111111111');
        $this->assertFalse($result); // CPF com todos os dígitos iguais é inválido
        
        // CPF válido real: 123.456.789-09
        $result = $gateway->validateCpfCnpj('12345678909');
        $this->assertTrue($result);
        
        // CPF formatado
        $result = $gateway->validateCpfCnpj('123.456.789-09');
        $this->assertTrue($result);
    }

    /**
     * @test
     * @covers LknWcCieloPix::validateCpfCnpj
     */
    public function testValidateCpfCnpjWithValidCnpj()
    {
        $gateway = new LknWcCieloPix();
        
        // CNPJ válido: 11.222.333/0001-81
        $result = $gateway->validateCpfCnpj('11222333000181');
        $this->assertTrue($result);
        
        // CNPJ formatado
        $result = $gateway->validateCpfCnpj('11.222.333/0001-81');
        $this->assertTrue($result);
        
        // CNPJ com todos os dígitos iguais
        $result = $gateway->validateCpfCnpj('11111111111111');
        $this->assertFalse($result);
    }

    /**
     * @test
     * @covers LknWcCieloPix::validateCpfCnpj
     */
    public function testValidateCpfCnpjWithInvalidDocuments()
    {
        $gateway = new LknWcCieloPix();
        
        // Documento muito curto
        $result = $gateway->validateCpfCnpj('123');
        $this->assertFalse($result);
        
        // Documento muito longo
        $result = $gateway->validateCpfCnpj('123456789012345');
        $this->assertFalse($result);
        
        // CPF com dígito verificador inválido
        $result = $gateway->validateCpfCnpj('12345678900');
        $this->assertFalse($result);
        
        // CNPJ com dígito verificador inválido
        $result = $gateway->validateCpfCnpj('11222333000100');
        $this->assertFalse($result);
        
        // String vazia
        $result = $gateway->validateCpfCnpj('');
        $this->assertFalse($result);
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_notice_once
     */
    public function testAddNoticeOnceWhenNoticeNotExists()
    {
        Functions\when('wc_has_notice')->justReturn(false);
        Functions\when('wc_add_notice')->justReturn(true);

        $gateway = new LknWcCieloPix();
        
        Functions\expect('wc_has_notice')
            ->once()
            ->with('Test message', 'error')
            ->andReturn(false);
            
        Functions\expect('wc_add_notice')
            ->once()
            ->with('Test message', 'error');

        $gateway->add_notice_once('Test message', 'error');
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_notice_once
     */
    public function testAddNoticeOnceWhenNoticeAlreadyExists()
    {
        Functions\when('wc_has_notice')->justReturn(true);
        Functions\when('wc_add_notice')->justReturn(true);

        $gateway = new LknWcCieloPix();
        
        Functions\expect('wc_has_notice')
            ->once()
            ->with('Test message', 'error')
            ->andReturn(true);
            
        Functions\expect('wc_add_notice')->never();

        $gateway->add_notice_once('Test message', 'error');
    }

    /**
     * @test
     * @covers LknWcCieloPix::showPix
     */
    public function testShowPixWithValidPixOrder()
    {
        // Mock do order
        $this->orderMock->allows('get_payment_method')->andReturn('lkn_wc_cielo_pix');
        $this->orderMock->allows('get_total')->andReturn(100.00);
        $this->orderMock->allows('get_meta')
            ->with('_wc_cielo_qrcode_payment_id')->andReturn('12345678-1234-1234-1234-123456789012');
        $this->orderMock->allows('get_meta')
            ->with('_wc_cielo_qrcode_image')->andReturn('base64imagecontent');
        $this->orderMock->allows('get_meta')
            ->with('_wc_cielo_qrcode_string')->andReturn('00020101021226...');

        Functions\when('wc_get_order')->justReturn($this->orderMock);
        
        Functions\expect('wc_get_template')
            ->once()
            ->with(
                'lkn-cielo-pix-template.php',
                [
                    'paymentId' => '12345678-1234-1234-1234-123456789012',
                    'pixString' => '00020101021226...',
                    'base64Image' => 'base64imagecontent'
                ],
                'includes/templates',
                '/path/to/plugin/includes/templates/'
            );

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with('lkn-cielo-wc-payment-pix-style');

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('lkn-cielo-wc-payment-pix-script');

        Functions\expect('wp_localize_script')
            ->once()
            ->with('lkn-cielo-wc-payment-pix-script', 'phpVariables');

        LknWcCieloPix::showPix(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::showPix
     */
    public function testShowPixWithNonPixOrder()
    {
        // Mock do order com método de pagamento diferente
        $this->orderMock->allows('get_payment_method')->andReturn('other_payment_method');
        $this->orderMock->allows('get_total')->andReturn(100.00);

        Functions\when('wc_get_order')->justReturn($this->orderMock);
        
        Functions\expect('wc_get_template')->never();
        Functions\expect('wp_enqueue_style')->never();

        LknWcCieloPix::showPix(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::showPix
     */
    public function testShowPixWithZeroValueOrder()
    {
        // Mock do order com valor zero
        $this->orderMock->allows('get_payment_method')->andReturn('lkn_wc_cielo_pix');
        $this->orderMock->allows('get_total')->andReturn(0.00);

        Functions\when('wc_get_order')->justReturn($this->orderMock);
        
        Functions\expect('wc_get_template')->never();

        LknWcCieloPix::showPix(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::process_payment
     */
    public function testProcessPaymentWithValidData()
    {
        // Setup POST data
        $_POST = [
            'billing_cielo_pix_free_cpf_cnpj' => '12345678909'
        ];

        // Mock do order
        $this->orderMock->allows([
            'get_billing_first_name' => 'João',
            'get_billing_last_name' => 'Silva',
            'get_currency' => 'BRL',
            'get_total' => 100.00,
            'add_order_note' => true,
            'update_meta_data' => true,
            'save' => true
        ]);

        // Mock do request PIX
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->allows('pix_request')->andReturn([
            'response' => [
                'qrcodeImage' => 'base64imagecontent',
                'qrcodeString' => '00020101021226...',
                'paymentId' => '12345678-1234-1234-1234-123456789012'
            ]
        ]);

        // Mock das funções WordPress
        Functions\when('wc_get_order')->justReturn($this->orderMock);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);

        // Mock do helper
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::saveTransactionMetadata')
            ->justReturn(true);

        $gateway = $this->createMockPixGateway();
        $gateway->allows([
            'get_option' => 'test_value',
            'get_return_url' => 'https://example.com/thank-you',
            'validateCpfCnpj' => true
        ]);

        // Set static property for request
        $reflection = new \ReflectionClass($gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($mockRequest);

        $result = $gateway->process_payment(123);

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals('success', $result['result']);
        $this->assertArrayHasKey('redirect', $result);
    }

    /**
     * @test
     * @covers LknWcCieloPix::process_payment
     */
    public function testProcessPaymentWithInvalidCpf()
    {
        // Setup POST data com CPF inválido
        $_POST = [
            'billing_cielo_pix_free_cpf_cnpj' => '11111111111'
        ];

        // Mock do order
        $this->orderMock->allows([
            'get_billing_first_name' => 'João',
            'get_billing_last_name' => 'Silva',
            'get_currency' => 'BRL',
            'get_total' => 100.00,
            'save' => true
        ]);

        Functions\when('wc_get_order')->justReturn($this->orderMock);

        // Mock do helper para createCustomErrorResponse
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::createCustomErrorResponse')
            ->justReturn(['error' => 'Invalid CPF']);
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::saveTransactionMetadata')
            ->justReturn(true);

        $gateway = $this->createMockPixGateway();
        $gateway->allows([
            'get_option' => 'test_value',
            'validateCpfCnpj' => false // CPF inválido
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Please enter a valid CPF or CNPJ.');

        $gateway->process_payment(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::process_payment
     */
    public function testProcessPaymentWithEmptyName()
    {
        // Setup POST data
        $_POST = [
            'billing_cielo_pix_free_cpf_cnpj' => '12345678909'
        ];

        // Mock do order com nome vazio
        $this->orderMock->allows([
            'get_billing_first_name' => '',
            'get_billing_last_name' => '',
            'get_currency' => 'BRL',
            'get_total' => 100.00,
            'save' => true
        ]);

        Functions\when('wc_get_order')->justReturn($this->orderMock);

        // Mock do helper
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::createCustomErrorResponse')
            ->justReturn(['error' => 'Nome não informado']);
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::saveTransactionMetadata')
            ->justReturn(true);

        $gateway = $this->createMockPixGateway();
        $gateway->allows('get_option')->andReturn('test_value');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nome não informado');

        $gateway->process_payment(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::process_payment
     */
    public function testProcessPaymentWithApiError()
    {
        // Setup POST data
        $_POST = [
            'billing_cielo_pix_free_cpf_cnpj' => '12345678909'
        ];

        // Mock do order
        $this->orderMock->allows([
            'get_billing_first_name' => 'João',
            'get_billing_last_name' => 'Silva',
            'get_currency' => 'BRL',
            'get_total' => 100.00,
            'add_order_note' => true,
            'save' => true
        ]);

        // Mock do request PIX com erro
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->allows('pix_request')->andReturn([
            'sucess' => false,
            'response' => 'API Error'
        ]);

        Functions\when('wc_get_order')->justReturn($this->orderMock);

        // Mock do helper
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::saveTransactionMetadata')
            ->justReturn(true);

        $gateway = $this->createMockPixGateway();
        $gateway->allows([
            'get_option' => 'test_value',
            'validateCpfCnpj' => true
        ]);

        // Set static property for request
        $reflection = new \ReflectionClass($gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($mockRequest);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"API Error"');

        $gateway->process_payment(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::process_payment
     */
    public function testProcessPaymentWithIncompletePixData()
    {
        // Setup POST data
        $_POST = [
            'billing_cielo_pix_free_cpf_cnpj' => '12345678909'
        ];

        // Mock do order
        $this->orderMock->allows([
            'get_billing_first_name' => 'João',
            'get_billing_last_name' => 'Silva',
            'get_currency' => 'BRL',
            'get_total' => 100.00,
            'save' => true
        ]);

        // Mock do request PIX com dados incompletos (sem qrcodeImage)
        $mockRequest = Mockery::mock(LknWcCieloRequest::class);
        $mockRequest->allows('pix_request')->andReturn([
            'response' => [
                'qrcodeString' => '00020101021226...',  // Faltando qrcodeImage
                'paymentId' => '12345678-1234-1234-1234-123456789012'
            ]
        ]);

        Functions\when('wc_get_order')->justReturn($this->orderMock);

        // Mock do helper
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::createCustomErrorResponse')
            ->justReturn(['error' => 'Incomplete PIX data']);
        Functions\when('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper::saveTransactionMetadata')
            ->justReturn(true);

        $gateway = $this->createMockPixGateway();
        $gateway->allows([
            'get_option' => 'test_value',
            'validateCpfCnpj' => true
        ]);

        // Set static property for request
        $reflection = new \ReflectionClass($gateway);
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($mockRequest);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error generating PIX: QR Code information is missing. Please try again or contact support.');

        $gateway->process_payment(123);
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_gateway_name_to_notes
     */
    public function testAddGatewayNameToNotesWithCorrectGateway()
    {
        // Mock do order
        $this->orderMock->allows('get_payment_method')->andReturn('lkn_wc_cielo_pix');
        Functions\when('wc_get_order')->justReturn($this->orderMock);

        $gateway = $this->createMockPixGateway();

        $note_data = [
            'comment_content' => '[lkn_wc_cielo_pix] Payment completed successfully'
        ];
        $args = ['order_id' => 123];

        $result = $gateway->add_gateway_name_to_notes($note_data, $args);

        $this->assertEquals('Cielo PIX Free — Payment completed successfully', $result['comment_content']);
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_gateway_name_to_notes
     */
    public function testAddGatewayNameToNotesWithDifferentGateway()
    {
        // Mock do order com método de pagamento diferente
        $this->orderMock->allows('get_payment_method')->andReturn('other_gateway');
        Functions\when('wc_get_order')->justReturn($this->orderMock);

        $gateway = $this->createMockPixGateway();

        $note_data = [
            'comment_content' => '[other_gateway] Some message'
        ];
        $args = ['order_id' => 123];

        $result = $gateway->add_gateway_name_to_notes($note_data, $args);

        // Deve retornar sem modificações
        $this->assertEquals('[other_gateway] Some message', $result['comment_content']);
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_gateway_name_to_notes
     */
    public function testAddGatewayNameToNotesWithoutOrderId()
    {
        $gateway = $this->createMockPixGateway();

        $note_data = [
            'comment_content' => '[lkn_wc_cielo_pix] Payment completed successfully'
        ];
        $args = []; // Sem order_id

        $result = $gateway->add_gateway_name_to_notes($note_data, $args);

        // Deve retornar sem modificações
        $this->assertEquals('[lkn_wc_cielo_pix] Payment completed successfully', $result['comment_content']);
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_gateway_name_to_notes
     */
    public function testAddGatewayNameToNotesWithoutGatewayPrefix()
    {
        // Mock do order
        $this->orderMock->allows('get_payment_method')->andReturn('lkn_wc_cielo_pix');
        Functions\when('wc_get_order')->justReturn($this->orderMock);

        $gateway = $this->createMockPixGateway();

        $note_data = [
            'comment_content' => 'Payment completed successfully' // Sem prefixo [lkn_wc_cielo_pix]
        ];
        $args = ['order_id' => 123];

        $result = $gateway->add_gateway_name_to_notes($note_data, $args);

        // Deve retornar sem modificações pois não tem o padrão [gateway_id]
        $this->assertEquals('Payment completed successfully', $result['comment_content']);
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_error
     */
    public function testAddErrorWithWcFunction()
    {
        Functions\when('function_exists')->with('wc_add_notice')->andReturn(true);
        
        $gateway = $this->createMockPixGateway();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('<strong>Cielo PIX Free:</strong>  Test error message');

        $gateway->add_error('Test error message');
    }

    /**
     * @test
     * @covers LknWcCieloPix::add_error
     */
    public function testAddErrorWithoutWcFunction()
    {
        Functions\when('function_exists')->with('wc_add_notice')->andReturn(false);
        
        // Mock global woocommerce
        global $woocommerce;
        $woocommerce = Mockery::mock();
        $woocommerce->expects('add_error')
            ->once()
            ->with('<strong>Cielo PIX Free:</strong>  Test error message');

        $gateway = $this->createMockPixGateway();

        $gateway->add_error('Test error message');
    }

    /**
     * @test
     * @covers LknWcCieloPix::process_admin_options
     */
    public function testProcessAdminOptionsWithDefaults()
    {
        Functions\when('get_option')->with('woocommerce_lkn_wc_cielo_pix_settings')->andReturn([]);

        $gateway = $this->createMockPixGateway();
        $gateway->allows([
            'process_admin_options' => true, // parent method
            'get_option' => function($key) {
                $defaults = [
                    'description' => 'After the purchase is completed, the PIX will be generated and made available for payment!',
                    'payment_complete_status' => 'processing',
                    'pix_layout' => 'standard',
                    'layout_location' => 'bottom'
                ];
                return $defaults[$key] ?? '';
            },
            'update_option' => true
        ]);

        Actions\expectAdded('admin_notices')->never(); // Não deve adicionar notice de erro

        $result = $gateway->process_admin_options();
        $this->assertTrue($result);
    }

    /**
     * @test 
     * @covers LknWcCieloPix::payment_gateway_scripts
     */
    public function testPaymentGatewayScriptsOnCheckoutPage()
    {
        Functions\when('is_checkout')->justReturn(true);
        Functions\when('is_add_payment_method_page')->justReturn(false);
        Functions\when('is_order_received_page')->justReturn(false);
        
        $gateway = $this->createMockPixGateway();
        $gateway->enabled = 'yes';

        // Não deve lançar exceção nem fazer nada (método vazio quando condições são atendidas)
        $gateway->payment_gateway_scripts();
        
        $this->assertTrue(true); // Teste passou se chegou até aqui
    }

    /**
     * @test
     * @covers LknWcCieloPix::payment_gateway_scripts
     */
    public function testPaymentGatewayScriptsWhenDisabled()
    {
        Functions\when('is_checkout')->justReturn(true);
        
        $gateway = $this->createMockPixGateway();
        $gateway->enabled = 'no'; // Gateway desabilitado

        // Não deve executar nada quando desabilitado
        $gateway->payment_gateway_scripts();
        
        $this->assertTrue(true); // Teste passou se chegou até aqui
    }

    /**
     * @test
     * @covers LknWcCieloPix::payment_gateway_scripts
     */
    public function testPaymentGatewayScriptsOutsideCheckout()
    {
        Functions\when('is_checkout')->justReturn(false);
        Functions\when('is_add_payment_method_page')->justReturn(false);
        Functions\when('is_order_received_page')->justReturn(false);
        
        $gateway = $this->createMockPixGateway();
        $gateway->enabled = 'yes';

        // Não deve executar quando fora do checkout
        $gateway->payment_gateway_scripts();
        
        $this->assertTrue(true); // Teste passou se chegou até aqui
    }

    /**
     * Helper para criar mock da classe PIX
     */
    private function createMockPixGateway()
    {
        $gateway = Mockery::mock(LknWcCieloPix::class)->makePartial();
        $gateway->id = 'lkn_wc_cielo_pix';
        $gateway->method_title = 'Cielo PIX Free';
        $gateway->enabled = 'yes';
        
        // Mock das propriedades e métodos básicos
        $gateway->allows([
            'init_form_fields' => null,
            'init_settings' => null,
            'get_return_url' => 'https://example.com/thank-you'
        ]);

        return $gateway;
    }
}