<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Inicializa o ambiente de testes usando Brain\Monkey para mockar WordPress/WooCommerce
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests
 */

// Require Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize Brain\Monkey for WordPress function mocking
\Brain\Monkey\setUp();

// Define WordPress constants that are commonly used
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content/');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . 'plugins/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// Define plugin constants
if (!defined('LKN_WC_CIELO_VERSION')) {
    define('LKN_WC_CIELO_VERSION', '1.29.1');
}

if (!defined('LKN_WC_CIELO_PLUGIN_DIR')) {
    define('LKN_WC_CIELO_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('LKN_WC_CIELO_PLUGIN_URL')) {
    define('LKN_WC_CIELO_PLUGIN_URL', 'http://example.org/wp-content/plugins/wc_cielo_payment_gateway/');
}

// Mock WordPress functions that don't exist in test environment
if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}

// Mock WordPress functions that don't exist in test environment
if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'https://example.com/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('is_plugin_active')) {
    function is_plugin_active($plugin) {
        // For tests, assume WooCommerce is always active
        return strpos($plugin, 'woocommerce') !== false;
    }
}

// Create mock classes for WooCommerce dependencies that don't exist in test environment
if (!class_exists('WC_Payment_Gateway')) {
    class WC_Payment_Gateway {
        public $id = '';
        public $icon = '';
        public $has_fields = true;
        public $supports = [];
        public $method_title = '';
        public $method_description = '';
        public $form_fields = [];
        public $title = ''; // Evita warning de propriedade dinâmica
        public $description = ''; // Evita warning de propriedade dinâmica
        public $settings = [];
        
        public function __construct() {}
        
        public function init_settings() {
            // Initialize settings - mock implementation
            return true;
        }
        
        public function get_option($key, $default = '') {
            return $default;
        }
        
        public function add_notice_once($message, $type) {
            return true;
        }
        
        public function get_return_url($order = null) {
            return 'http://example.com/checkout/return';
        }
    }
}

if (!class_exists('WC_Logger')) {
    class WC_Logger {
        public function log($level, $message, $context = []) {
            return true;
        }
    }
}

if (!class_exists('WC_Order')) {
    class WC_Order {
        private $meta_data = [];
        
        public function get_id() {
            return 123;
        }
        
        public function get_total() {
            return 100.00;
        }
        
        public function get_currency() {
            return 'BRL';
        }
        
        public function get_transaction_id() {
            return 'test-transaction-123';
        }
        
        public function set_transaction_id($transaction_id) {
            return true;
        }
        
        public function add_order_note($note) {
            return true;
        }
        
        public function update_meta_data($key, $value, $unique = false) {
            $this->meta_data[$key] = $value;
            return true;
        }
        
        public function get_meta($key, $single = true) {
            return isset($this->meta_data[$key]) ? $this->meta_data[$key] : '';
        }
        
        public function save() {
            return true;
        }
        
        public function get_payment_method() {
            return 'lkn_cielo_credit';
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
            }
        }
        
        public function get_error_message() {
            return 'Test error message';
        }
        
        public function get_error_messages() {
            return ['Test error message'];
        }
    }
}

// Load helper functions for tests
require_once __DIR__ . '/TestHelpers.php';

// Clean up after each test
register_shutdown_function(function() {
    \Brain\Monkey\tearDown();
});
