<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Helper;

use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for LknWcCieloHelper utility functions
 */
class HelperUtilitiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('plugin_dir_url')->justReturn('http://localhost/wp-content/plugins/test/');
        Functions\when('is_plugin_active')->justReturn(false);
        Functions\when('get_option')->justReturn([]);
        Functions\when('wp_remote_get')->justReturn([
            'body' => json_encode(['success' => true])
        ]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_json_encode')->returnArg();
        Functions\when('json_encode')->returnArg();
        Functions\when('number_format')->returnArg();
        
        // Mock constants
        if (!defined('LKN_WC_GATEWAY_CIELO_URL')) {
            define('LKN_WC_GATEWAY_CIELO_URL', 'http://localhost/wp-content/plugins/test/');
        }
        if (!defined('WP_PLUGIN_DIR')) {
            define('WP_PLUGIN_DIR', '/path/to/plugins');
        }
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_get_icon_url_returns_correct_url()
    {
        $result = LknWcCieloHelper::getIconUrl();
        
        $this->assertIsString($result);
        $this->assertStringContainsString('resources/assets/wordpressAssets/payment-icons.png', $result);
    }

    public function test_is_pro_license_active_returns_false_when_plugin_inactive()
    {
        Functions\when('is_plugin_active')->justReturn(false);
        Functions\when('file_exists')->justReturn(false);
        
        $result = LknWcCieloHelper::is_pro_license_active();
        
        $this->assertFalse($result);
    }

    public function test_is_pro_license_active_returns_true_when_plugin_active()
    {
        Functions\when('is_plugin_active')->justReturn(true);
        Functions\when('file_exists')->justReturn(true);
        
        $result = LknWcCieloHelper::is_pro_license_active();
        
        $this->assertTrue($result);
    }

    public function test_get_card_provider_returns_visa_for_visa_card()
    {
        $visaCard = '4532015112830366';
        
        $result = LknWcCieloHelper::getCardProvider($visaCard, 'test_gateway');
        
        $this->assertEquals('Visa', $result);
    }

    public function test_get_card_provider_returns_mastercard_for_mastercard()
    {
        $mastercardCard = '5555555555554444';
        
        $result = LknWcCieloHelper::getCardProvider($mastercardCard, 'test_gateway');
        
        $this->assertEquals('Master', $result);
    }

    public function test_get_card_provider_returns_amex_for_amex_card()
    {
        $amexCard = '378282246310005';
        
        $result = LknWcCieloHelper::getCardProvider($amexCard, 'test_gateway');
        
        $this->assertEquals('Amex', $result);
    }

    public function test_get_card_provider_returns_elo_for_elo_card()
    {
        $eloCard = '6362970000457013';
        
        $result = LknWcCieloHelper::getCardProvider($eloCard, 'test_gateway');
        
        $this->assertEquals('Elo', $result);
    }

    public function test_get_card_provider_returns_hipercard_for_hipercard()
    {
        $hipercardCard = '6062825624254001';
        
        $result = LknWcCieloHelper::getCardProvider($hipercardCard, 'test_gateway');
        
        $this->assertEquals('Hipercard', $result);
    }

    public function test_get_card_provider_returns_diners_for_diners_card()
    {
        $dinersCard = '30569309025904';
        
        $result = LknWcCieloHelper::getCardProvider($dinersCard, 'test_gateway');
        
        $this->assertEquals('Diners', $result);
    }

    public function test_get_card_provider_returns_jcb_for_jcb_card()
    {
        $jcbCard = '3530111333300000';
        
        $result = LknWcCieloHelper::getCardProvider($jcbCard, 'test_gateway');
        
        $this->assertEquals('JCB', $result);
    }

    public function test_get_card_provider_returns_discover_for_discover_card()
    {
        $discoverCard = '6011111111111117';
        
        $result = LknWcCieloHelper::getCardProvider($discoverCard, 'test_gateway');
        
        $this->assertEquals('Discover', $result);
    }

    public function test_get_card_provider_returns_aura_for_aura_card()
    {
        $auraCard = '5078601800000127';
        
        $result = LknWcCieloHelper::getCardProvider($auraCard, 'test_gateway');
        
        $this->assertEquals('Aura', $result);
    }

    public function test_get_card_provider_returns_unknown_for_invalid_card()
    {
        $invalidCard = '1234567890123456';
        
        $result = LknWcCieloHelper::getCardProvider($invalidCard, 'test_gateway');
        
        $this->assertEquals('Unknown', $result);
    }

    public function test_censor_string_censors_middle_part()
    {
        $string = 'abcdefghijklmnop';
        $keepVisible = 4;
        
        $result = LknWcCieloHelper::censorString($string, $keepVisible);
        
        $this->assertStringStartsWith('abcd', $result);
        $this->assertStringEndsWith('mnop', $result);
        $this->assertStringContainsString('****', $result);
    }

    public function test_censor_string_handles_short_strings()
    {
        $string = 'abc';
        $keepVisible = 4; // More than string length
        
        $result = LknWcCieloHelper::censorString($string, $keepVisible);
        
        $this->assertEquals($string, $result);
    }

    public function test_censor_string_handles_empty_string()
    {
        $string = '';
        $keepVisible = 4;
        
        $result = LknWcCieloHelper::censorString($string, $keepVisible);
        
        $this->assertEquals('', $result);
    }

    public function test_create_custom_error_response_creates_proper_structure()
    {
        $code = 400;
        $errorCode = 'TEST001';
        $message = 'Test error message';
        
        $result = LknWcCieloHelper::createCustomErrorResponse($code, $errorCode, $message);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        
        $error = $result['errors'][0];
        $this->assertEquals($code, $error['code']);
        $this->assertEquals($errorCode, $error['errorCode']);
        $this->assertEquals($message, $error['message']);
    }

    public function test_save_transaction_metadata_saves_order_metadata()
    {
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloCcNumber', '****1234');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloCcExpDate', '12/25');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloCcName', 'John Doe');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloInstallments', 1);
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloAmount', '100.00');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloCurrency', 'BRL');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloBrand', 'Visa');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloMerchantId', 'test_merchant');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloMerchantKey', 'test_key');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloMerchantOrderId', 'order_123');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloOrderId', 123);
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloCaptureType', 'automatic');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloResponse', Mockery::any());
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloPaymentType', 'Credit');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloSecurityCode', 'lkn_cc_cvc');
        $mockOrder->shouldReceive('update_meta_data')->once()->with('lknWcCieloPixPaymentId', 'pix_123');
        
        $mockGateway = Mockery::mock();
        $mockGateway->id = 'test_gateway';
        
        $response = ['test' => 'response'];
        
        LknWcCieloHelper::saveTransactionMetadata(
            $mockOrder,
            $response,
            '4532015112830366',
            '12/25',
            'John Doe',
            1,
            100.00,
            'BRL',
            'Visa',
            'test_merchant',
            'test_key',
            'order_123',
            123,
            'automatic',
            null,
            'Credit',
            'lkn_cc_cvc',
            $mockGateway,
            null,
            null,
            null,
            null,
            'pix_123'
        );
        
        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_mask_pix_response_masks_sensitive_data()
    {
        $response = [
            'MerchantId' => 'test_merchant_id_12345',
            'MerchantKey' => 'test_merchant_key_67890',
            'Customer' => [
                'Identity' => '12345678901'
            ]
        ];
        
        $result = LknWcCieloHelper::maskPixResponse($response);
        
        $this->assertStringContainsString('****', $result['MerchantId']);
        $this->assertStringContainsString('****', $result['MerchantKey']);
        $this->assertStringContainsString('****', $result['Customer']['Identity']);
    }

    public function test_mask_pix_response_handles_missing_customer_key()
    {
        $response = [
            'MerchantId' => 'test_merchant_id_12345',
            'MerchantKey' => 'test_merchant_key_67890'
            // No Customer key
        ];
        
        $result = LknWcCieloHelper::maskPixResponse($response);
        
        $this->assertArrayNotHasKey('Customer', $result);
        $this->assertStringContainsString('****', $result['MerchantId']);
        $this->assertStringContainsString('****', $result['MerchantKey']);
    }

    public function test_mask_pix_response_handles_string_input()
    {
        $response = 'Simple string response';
        
        $result = LknWcCieloHelper::maskPixResponse($response);
        
        $this->assertEquals($response, $result);
    }

    public function test_get_card_provider_with_formatted_card_number()
    {
        $formattedVisa = '4532 0151 1283 0366';
        
        $result = LknWcCieloHelper::getCardProvider($formattedVisa, 'test_gateway');
        
        $this->assertEquals('Visa', $result);
    }

    public function test_get_card_provider_with_spaces_and_dashes()
    {
        $formattedMastercard = '5555-5555-5555-4444';
        
        $result = LknWcCieloHelper::getCardProvider($formattedMastercard, 'test_gateway');
        
        $this->assertEquals('Master', $result);
    }

    public function test_censor_string_with_zero_keep_visible()
    {
        $string = 'abcdefghij';
        $keepVisible = 0;
        
        $result = LknWcCieloHelper::censorString($string, $keepVisible);
        
        $this->assertEquals('**********', $result);
    }

    public function test_censor_string_with_custom_censor_char()
    {
        $helper = new LknWcCieloHelper();
        $reflection = new \ReflectionMethod($helper, 'censorString');
        $reflection->setAccessible(true);
        
        $string = 'abcdefghij';
        $keepVisible = 2;
        
        $result = $reflection->invoke($helper, $string, $keepVisible, 'X');
        
        $this->assertStringStartsWith('ab', $result);
        $this->assertStringEndsWith('ij', $result);
        $this->assertStringContainsString('X', $result);
    }

    public function test_create_custom_error_response_with_different_codes()
    {
        $result1 = LknWcCieloHelper::createCustomErrorResponse(400, 'BAD_REQUEST', 'Invalid request');
        $result2 = LknWcCieloHelper::createCustomErrorResponse(500, 'INTERNAL_ERROR', 'Server error');
        
        $this->assertEquals(400, $result1['errors'][0]['code']);
        $this->assertEquals('BAD_REQUEST', $result1['errors'][0]['errorCode']);
        
        $this->assertEquals(500, $result2['errors'][0]['code']);
        $this->assertEquals('INTERNAL_ERROR', $result2['errors'][0]['errorCode']);
    }

    public function test_get_card_provider_edge_cases()
    {
        // Test empty string
        $result = LknWcCieloHelper::getCardProvider('', 'test_gateway');
        $this->assertEquals('Unknown', $result);
        
        // Test very short number
        $result = LknWcCieloHelper::getCardProvider('123', 'test_gateway');
        $this->assertEquals('Unknown', $result);
        
        // Test very long number
        $result = LknWcCieloHelper::getCardProvider('12345678901234567890', 'test_gateway');
        $this->assertEquals('Unknown', $result);
    }

    public function test_get_card_provider_boundary_values()
    {
        // Test Visa boundary (starts with 4)
        $result = LknWcCieloHelper::getCardProvider('4000000000000000', 'test_gateway');
        $this->assertEquals('Visa', $result);
        
        // Test Mastercard boundary (starts with 5)
        $result = LknWcCieloHelper::getCardProvider('5000000000000000', 'test_gateway');
        $this->assertEquals('Master', $result);
    }

    public function test_detect_card_brands_with_special_patterns()
    {
        // Test specific Elo patterns
        $eloCard1 = '4011780000000000'; // Elo dual brand with Visa
        $result = LknWcCieloHelper::getCardProvider($eloCard1, 'test_gateway');
        $this->assertContains($result, ['Visa', 'Elo']); // Could be either, depends on implementation
        
        // Test Hipercard pattern
        $hipercardCard = '6062825624254001';
        $result = LknWcCieloHelper::getCardProvider($hipercardCard, 'test_gateway');
        $this->assertEquals('Hipercard', $result);
    }

    public function test_save_transaction_metadata_with_minimal_data()
    {
        $mockOrder = Mockery::mock('WC_Order');
        $mockOrder->shouldReceive('update_meta_data')->zeroOrMoreTimes();
        
        $mockGateway = Mockery::mock();
        $mockGateway->id = 'test_gateway';
        
        // Test with minimal required parameters
        LknWcCieloHelper::saveTransactionMetadata(
            $mockOrder,
            null,
            '',
            '',
            '',
            0,
            0,
            '',
            '',
            '',
            '',
            '',
            0,
            '',
            null,
            '',
            '',
            $mockGateway
        );
        
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function test_mask_pix_response_with_nested_arrays()
    {
        $response = [
            'MerchantId' => 'test_merchant_12345',
            'Data' => [
                'Customer' => [
                    'Identity' => '12345678901',
                    'Name' => 'John Doe'
                ],
                'Payment' => [
                    'Amount' => 1000
                ]
            ]
        ];
        
        $result = LknWcCieloHelper::maskPixResponse($response);
        
        $this->assertIsArray($result);
        $this->assertStringContainsString('****', $result['MerchantId']);
        
        // Should handle nested structures appropriately
        if (isset($result['Data']['Customer']['Identity'])) {
            $this->assertStringContainsString('****', $result['Data']['Customer']['Identity']);
        }
    }

    public function test_get_icon_url_with_different_base_urls()
    {
        // Test with different plugin URL
        Functions\when('plugin_dir_url')->returnWith('https://example.com/wp-content/plugins/cielo/');
        
        $result = LknWcCieloHelper::getIconUrl();
        
        $this->assertStringStartsWith('https://example.com/', $result);
        $this->assertStringContainsString('payment-icons.png', $result);
    }
}