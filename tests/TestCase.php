<?php
/**
 * Base Test Case
 * 
 * Classe base para todos os testes unitários do plugin
 * Configura Brain\Monkey e Mockery para cada teste
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests
 */

namespace Lkn\WCCieloPaymentGateway\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Base TestCase class
 * 
 * All test classes should extend this class to benefit from:
 * - Brain\Monkey setup/teardown
 * - Mockery integration
 * - Common WordPress/WooCommerce mocks
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        
        // Set up common WordPress functions that are used everywhere
        $this->setupCommonWordPressFunctions();
    }

    /**
     * Tear down test environment after each test
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Set up common WordPress functions used throughout the plugin
     * These are mocked to return sensible defaults
     */
    protected function setupCommonWordPressFunctions(): void
    {
        // Translation functions
        Monkey\Functions\when('__')->returnArg();
        Monkey\Functions\when('_e')->returnArg();
        Monkey\Functions\when('_x')->returnArg();
        Monkey\Functions\when('_n')->returnArg();
        Monkey\Functions\when('esc_html__')->returnArg();
        Monkey\Functions\when('esc_html_e')->returnArg();
        Monkey\Functions\when('esc_attr__')->returnArg();
        Monkey\Functions\when('esc_attr_e')->returnArg();

        // Sanitization functions
        Monkey\Functions\when('sanitize_text_field')->returnArg();
        Monkey\Functions\when('sanitize_email')->returnArg();
        Monkey\Functions\when('sanitize_key')->returnArg();
        Monkey\Functions\when('esc_html')->returnArg();
        Monkey\Functions\when('esc_attr')->returnArg();
        Monkey\Functions\when('esc_url')->returnArg();
        Monkey\Functions\when('wp_unslash')->returnArg();

        // Common WordPress functions
        Monkey\Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return is_array($args) ? array_merge($defaults, $args) : $defaults;
        });
        
        Monkey\Functions\when('is_wp_error')->alias(function($thing) {
            return $thing instanceof \WP_Error;
        });

        // Option functions (default behavior)
        Monkey\Functions\when('get_option')->justReturn([]);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('delete_option')->justReturn(true);

        // Plugin functions
        Monkey\Functions\when('plugin_dir_path')->returnArg();
        Monkey\Functions\when('plugin_dir_url')->returnArg();
        
        // WooCommerce functions commonly used
        Monkey\Functions\when('wc_get_order')->justReturn($this->createMockOrder());
        Monkey\Functions\when('wc_get_logger')->justReturn($this->createMockLogger());
        Monkey\Functions\when('wc_add_notice')->justReturn(true);
        Monkey\Functions\when('wc_has_notice')->justReturn(false);
        Monkey\Functions\when('add_action')->justReturn(true);
        Monkey\Functions\when('add_filter')->justReturn(true);
        // Note: do_action not mocked globally to allow Brain\Monkey Actions\expectDone to work
        
        // Time and scheduling functions
        Monkey\Functions\when('current_time')->alias(function($type = 'mysql', $gmt = 0) {
            if ($type === 'timestamp') {
                return time();
            }
            return date('Y-m-d H:i:s');
        });
        
        Monkey\Functions\when('wp_schedule_single_event')->justReturn(true);
        Monkey\Functions\when('wp_next_scheduled')->justReturn(false);
        
        // WordPress filter/action functions
        Monkey\Functions\when('apply_filters')->alias(function($hook_name, $value) {
            // Return the default value (second parameter) instead of the hook name
            return $value;
        });
        
        // WordPress HTTP functions
        Monkey\Functions\when('wp_remote_post')->justReturn([
            'body' => json_encode(['success' => true]),
            'response' => ['code' => 200]
        ]);
        Monkey\Functions\when('wp_remote_get')->justReturn([
            'body' => json_encode(['success' => true]),
            'response' => ['code' => 200]
        ]);
        Monkey\Functions\when('wp_remote_request')->justReturn([
            'body' => json_encode(['success' => true]),
            'response' => ['code' => 200]
        ]);
        
        // WordPress nonce functions (for security)
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('wp_create_nonce')->justReturn('valid_nonce');
        
        // WordPress cron functions  
        Monkey\Functions\when('wp_schedule_single_event')->justReturn(true);
        Monkey\Functions\when('wp_unschedule_event')->justReturn(true);
        Monkey\Functions\when('wp_next_scheduled')->justReturn(false);
        
        // WordPress user functions
        Monkey\Functions\when('get_current_user_id')->justReturn(1);
        Monkey\Functions\when('current_user_can')->justReturn(true);
    }

    /**
     * Mock wp_remote_get to return a specific response
     *
     * @param array|WP_Error $response The response to return
     */
    protected function mockWpRemoteGet($response): void
    {
        Monkey\Functions\when('wp_remote_get')->justReturn($response);
    }

    /**
     * Mock wp_remote_retrieve_body
     *
     * @param string $body The body to return
     */
    protected function mockWpRemoteRetrieveBody(string $body): void
    {
        Monkey\Functions\when('wp_remote_retrieve_body')->justReturn($body);
    }

    /**
     * Mock wp_remote_retrieve_response_code
     *
     * @param int $code The response code to return
     */
    protected function mockWpRemoteRetrieveResponseCode(int $code): void
    {
        Monkey\Functions\when('wp_remote_retrieve_response_code')->justReturn($code);
    }

    /**
     * Create a mock WC_Order object with common methods
     *
     * @param int $orderId Order ID
     * @param float $total Order total
     * @return \Mockery\MockInterface
     */
    protected function createMockOrder(int $orderId = 123, float $total = 100.00)
    {
        $order = \Mockery::mock('WC_Order');
        $order->shouldReceive('get_id')->andReturn($orderId);
        $order->shouldReceive('get_total')->andReturn($total);
        $order->shouldReceive('get_currency')->andReturn('BRL');
        $order->shouldReceive('get_order_number')->andReturn((string)$orderId);
        $order->shouldReceive('get_billing_first_name')->andReturn('John');
        $order->shouldReceive('get_billing_last_name')->andReturn('Doe');
        $order->shouldReceive('get_billing_email')->andReturn('john@example.com');
        $order->shouldReceive('get_billing_phone')->andReturn('1234567890');
        $order->shouldReceive('get_transaction_id')->andReturn('TID' . $orderId);
        
        // Meta data methods
        $order->shouldReceive('get_meta')->andReturn('');
        $order->shouldReceive('update_meta_data')->andReturn(null);
        $order->shouldReceive('delete_meta_data')->andReturn(null);
        $order->shouldReceive('save')->andReturn(true);
        
        // Status methods
        $order->shouldReceive('get_status')->andReturn('pending');
        $order->shouldReceive('update_status')->andReturn(true);
        $order->shouldReceive('add_order_note')->andReturn(true);
        
        return $order;
    }

    /**
     * Mock wp_remote_post to return a specific response
     *
     * @param array|WP_Error $response The response to return
     */
    protected function mockWpRemotePost($response): void
    {
        Monkey\Functions\when('wp_remote_post')->justReturn($response);
    }

    /**
     * Create a mock WC_Logger object
     *
     * @return \Mockery\MockInterface
     */
    protected function createMockLogger()
    {
        $logger = \Mockery::mock('WC_Logger');
        $logger->shouldReceive('log')->andReturn(true);
        $logger->shouldReceive('info')->andReturn(true);
        $logger->shouldReceive('error')->andReturn(true);
        $logger->shouldReceive('debug')->andReturn(true);
        $logger->shouldReceive('warning')->andReturn(true);
        $logger->shouldReceive('notice')->andReturn(true);
        
        return $logger;
    }

    /**
     * Assert that a string is properly masked (contains asterisks)
     *
     * @param string $value The value to check
     * @param string $message Optional assertion message
     */
    protected function assertStringIsMasked(string $value, string $message = ''): void
    {
        $this->assertStringContainsString('*', $value, $message ?: 'String should contain masking asterisks');
        $this->assertNotEmpty($value, $message ?: 'Masked string should not be empty');
    }

    /**
     * Assert that a string does not contain sensitive data
     *
     * @param string $haystack The string to search in
     * @param string $needle The sensitive data that should not be present
     * @param string $message Optional assertion message
     */
    protected function assertStringDoesNotContainSensitiveData(string $haystack, string $needle, string $message = ''): void
    {
        $this->assertStringNotContainsString(
            $needle,
            $haystack,
            $message ?: 'String should not contain sensitive data'
        );
    }
}
