<?php

namespace Lkn\WcCieloPaymentGateway\Includes;

use Lkn\WcCieloPaymentGateway\Admin\Lkn_Wc_Cielo_Payment_Gateway_Admin;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Payment_Gateway_Loader;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Gateway_Cielo_Credit;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Gateway_Cielo_Debit;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Pix;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Gateway_Cielo_Google_Pay;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Gateway_Cielo_Endpoint;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Helper;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Credit_Blocks;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Debit_Blocks;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Pix_Blocks;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Gateway_Cielo_Google_Pay_Blocks;
use Lkn\WcCieloPaymentGateway\PublicView\Lkn_Wc_Cielo_Payment_Gateway_Public;
use Lkn\WcCieloPaymentGateway\Services\ServiceContainer;
use Lkn\WcCieloPaymentGateway\Services\HttpClient;
use Lkn\WcCieloPaymentGateway\Services\SimpleSettingsManager;
use Lkn\WcCieloPaymentGateway\Services\WebhookRouter;
use Lkn\WcCieloPaymentGateway\Gateways\Cielo\CieloGateway;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknWcCieloPaymentGateway
 * @subpackage LknWcCieloPaymentGateway/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    LknWcCieloPaymentGateway
 * @subpackage LknWcCieloPaymentGateway/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
final class Lkn_Wc_Cielo_Payment_Gateway
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Lkn_Wc_Cielo_Payment_Gateway_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Service Container for dependency injection
     *
     * @since    1.25.0
     * @access   protected
     * @var      \Lkn\WcCieloPaymentGateway\Services\ServiceContainer    $serviceContainer    Service container instance.
     */
    protected $serviceContainer;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('WC_CIELO_PAYMENT_GATEWAY_VERSION')) {
            $this->version = WC_CIELO_PAYMENT_GATEWAY_VERSION;
        } else {
            $this->version = '1.25.0';
        }
        $this->plugin_name = 'lkn-wc-cielo-payment-gateway';

        $this->lkn_load_dependencies();
        $this->lkn_setup_service_container();
        $this->loader->lkn_add_action('plugins_loaded', $this, 'lkn_define_hooks');
    }

    // Gateway classes
    public $lkn_wc_gateway_cielo_credit;
    public $lkn_wc_gateway_cielo_debit;
    public $lkn_wc_cielo_pix;
    public $lkn_wc_gateway_cielo_google_pay;
    public $lkn_wc_gateway_cielo_endpoint;
    public $lkn_wc_cielo_helper;

    /**
     * Define os hooks somente quando woocommerce está ativo
     */
    public function lkn_define_hooks(): void
    {
        if (class_exists('WooCommerce')) {
            // Inject Service Container into gateway classes
            \Lkn\WcCieloPaymentGateway\Services\GatewayServiceAdapter::setServiceContainer($this->serviceContainer);
            
            $this->lkn_wc_cielo_pix = new Lkn_Wc_Cielo_Pix();
            $this->lkn_define_admin_hooks();
            $this->lkn_define_public_hooks();
        } else {
            // Aguarda WooCommerce ser carregado
            $this->loader->lkn_add_action('admin_notices', $this, 'lkn_woocommerce_missing_notice');
        }
        $this->lkn_run();
    }

    public function lkn_woocommerce_missing_notice(): void
    {
        deactivate_plugins(plugin_basename(WC_CIELO_PAYMENT_GATEWAY_BASE_FILE));
        include_once __DIR__ . '/views/notices/html-notice-woocommerce-missing.php';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Lkn_Wc_Cielo_Payment_Gateway_Loader. Orchestrates the hooks of the plugin.
     * - Lkn_Wc_Cielo_Payment_Gateway_I18n. Defines internationalization functionality.
     * - Lkn_Wc_Cielo_Payment_Gateway_Admin. Defines all hooks for the admin area.
     * - Lkn_Wc_Cielo_Payment_Gateway_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function lkn_load_dependencies(): void
    {
        $this->loader = new Lkn_Wc_Cielo_Payment_Gateway_Loader();
        $this->lkn_wc_gateway_cielo_endpoint = new Lkn_Wc_Gateway_Cielo_Endpoint();
        $this->lkn_wc_cielo_helper = new Lkn_Wc_Cielo_Helper();
    }

    /**
     * Setup service container and register services
     *
     * @since 1.25.0
     * @access private
     */
    private function lkn_setup_service_container(): void
    {
        $this->serviceContainer = new ServiceContainer();

        // Register core services
        $this->serviceContainer->register('httpClient', function() {
            return new HttpClient();
        });

        $this->serviceContainer->register('settingsManager', function() {
            return new SimpleSettingsManager();
        });

        $this->serviceContainer->register('webhookRouter', function($container) {
            return new WebhookRouter();
        });

        // Register Cielo gateway
        $this->serviceContainer->register('cieloGateway', function($container) {
            return new CieloGateway(
                $container->get('httpClient'),
                $container->get('settingsManager'),
                [] // Configuration will be set by individual gateway classes
            );
        });
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function lkn_define_admin_hooks(): void
    {
        $plugin_admin = new Lkn_Wc_Cielo_Payment_Gateway_Admin($this->lkn_get_plugin_name(), $this->lkn_get_version());

        $this->loader->lkn_add_action('admin_enqueue_scripts', $plugin_admin, 'lkn_enqueue_styles');
        $this->loader->lkn_add_action('admin_enqueue_scripts', $plugin_admin, 'lkn_enqueue_scripts');
        $this->loader->lkn_add_action('admin_notices', $this, 'lkn_admin_notice');
        $this->loader->lkn_add_filter('plugin_action_links_' . WC_CIELO_PAYMENT_GATEWAY_FILE_BASENAME, $this, 'lkn_wc_cielo_plugin_row_meta', 10, 2);
        $this->loader->lkn_add_filter('plugin_action_links_' . WC_CIELO_PAYMENT_GATEWAY_FILE_BASENAME, $this, 'lkn_wc_cielo_plugin_row_meta_pro', 10, 2);
        // Admin settings card for specific sections
        $this->lkn_setup_admin_settings_card();
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function lkn_define_public_hooks(): void
    {
        $plugin_public = new Lkn_Wc_Cielo_Payment_Gateway_Public($this->lkn_get_plugin_name(), $this->lkn_get_version());

        $this->loader->lkn_add_action('wp_enqueue_scripts', $plugin_public, 'lkn_enqueue_styles');
        $this->loader->lkn_add_action('wp_enqueue_scripts', $plugin_public, 'lkn_enqueue_scripts');
        $this->loader->lkn_add_filter('woocommerce_payment_gateways', $this, 'lkn_add_gateway');
        $this->loader->lkn_add_action('rest_api_init', $this->lkn_wc_gateway_cielo_endpoint, 'lkn_register_order_capture_endpoint');
        $this->loader->lkn_add_action('add_meta_boxes', $this->lkn_wc_cielo_helper, 'lkn_show_order_logs');
        $this->loader->lkn_add_action('woocommerce_order_details_after_order_table', $this->lkn_wc_cielo_pix, 'lkn_show_pix');
        $this->loader->lkn_add_filter('woocommerce_get_order_item_totals', $this, 'lkn_new_order_item_totals', 10, 3);
        // WooCommerce Blocks compatibility
        $this->loader->lkn_add_action('before_woocommerce_init', $this, 'lkn_wc_editor_blocks_active');
        $this->loader->lkn_add_action('woocommerce_blocks_payment_method_type_registration', $this, 'lkn_wc_editor_blocks_add_payment_method');

    }

    /**
     * Setup admin settings card for specific gateway sections
     *
     * @since    1.0.0
     * @access   private
     */
    private function lkn_setup_admin_settings_card(): void
    {
        $page    = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab     = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        $sections = [
            'lkn_cielo_credit',
            'lkn_cielo_debit',
            'lkn_wc_cielo_pix',
            'lkn_cielo_pix',
            'lkn_cielo_boleto',
            'lkn_cielo_google_pay'
        ];

        if (
            $page === 'wc-settings' &&
            $tab === 'checkout' &&
            in_array($section, $sections, true)
        ) {
            $this->loader->lkn_add_action('woocommerce_init', $this, 'lkn_load_admin_settings_assets');
        }
    }

    /**
     * Load admin settings assets
     *
     * @since    1.0.0
     */
    public function lkn_load_admin_settings_assets(): void
    {
        $versions = 'Plugin Cielo v' . WC_CIELO_PAYMENT_GATEWAY_VERSION;
        if (defined('LKN_CIELO_API_PRO_VERSION')) {
            $versions .= ' | Cielo Pro v' . LKN_CIELO_API_PRO_VERSION;
        } else {
            $versions .= ' | WooCommerce v' . WC()->version;
        }

        wp_enqueue_script('lknCieloForWoocommerceCard', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/js/lkn-woocommerce-admin-card.js', array('jquery'), WC_CIELO_PAYMENT_GATEWAY_VERSION, false);
        wp_enqueue_style('lknCieloForWoocommerceCard', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/css/lkn-woocommerce-admin-card.css', array(), WC_CIELO_PAYMENT_GATEWAY_VERSION, 'all');
        wp_enqueue_script('lknCieloForWoocommerceProSettings', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/js/lkn-settings-pro-fields.js', array(), WC_CIELO_PAYMENT_GATEWAY_VERSION, false);

        wp_localize_script(
            'lknCieloForWoocommerceProSettings',
            'lknCieloProSettingsVars',
            array(
                'proOnly' => __('Available only in PRO', 'lkn-wc-gateway-cielo'),
            )
        );

        wc_get_template(
            'adminSettingsCard.php',
            array(
                'backgrounds' => array(
                    'right' => WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'includes/assets/img/backgroundCardRight.svg',
                    'left' => WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'includes/assets/img/backgroundCardLeft.svg'
                ),
                'logo' => WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'includes/assets/img/linkNacionalLogo.webp',
                'whatsapp' => WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'includes/assets/img/whatsapp-icon.svg',
                'telegram' => WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'includes/assets/img/telegram-icon.svg',
                'versions' => $versions
            ),
            'woocommerce/adminSettingsCard/',
            WC_CIELO_PAYMENT_GATEWAY_DIR . '/includes/templates/'
        );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function lkn_run(): void
    {
        $this->loader->lkn_run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function lkn_get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Lkn_Wc_Cielo_Payment_Gateway_Loader    Orchestrates the hooks of the plugin.
     */
    public function lkn_get_loader()
    {
        return $this->loader;
    }

    /**
     * Get the service container instance
     *
     * @since     1.25.0
     * @return    \Lkn\WcCieloPaymentGateway\Services\ServiceContainer    Service container instance.
     */
    public function lkn_get_service_container()
    {
        return $this->serviceContainer;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function lkn_get_version()
    {
        return $this->version;
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array $gateways
     */
    public function lkn_add_gateway($methods)
    {
        $lkn_wc_gateway_cielo_credit_class = new Lkn_Wc_Gateway_Cielo_Credit();
        $lkn_wc_gateway_cielo_debit_class = new Lkn_Wc_Gateway_Cielo_Debit();
        $lkn_wc_gateway_cielo_pix_class = new Lkn_Wc_Cielo_Pix();
        $lkn_wc_gateway_cielo_google_pay_class = new Lkn_Wc_Gateway_Cielo_Google_Pay();

        array_push($methods, $lkn_wc_gateway_cielo_credit_class);
        array_push($methods, $lkn_wc_gateway_cielo_debit_class);
        array_push($methods, $lkn_wc_gateway_cielo_pix_class);
        array_push($methods, $lkn_wc_gateway_cielo_google_pay_class);

        return $methods;
    }

    /**
     * Show admin notice for fraud detection plugin
     */
    public function lkn_admin_notice(): void
    {
        if (!file_exists(WP_PLUGIN_DIR . '/fraud-detection-for-woocommerce/fraud-detection-for-woocommerce.php') && (!is_plugin_active('integration-rede-for-woocommerce/integration-rede-for-woocommerce.php') && !is_plugin_active('woo-rede/integration-rede-for-woocommerce.php'))) {
            require WC_CIELO_PAYMENT_GATEWAY_DIR . 'includes/views/notices/LknWcCieloDownloadNotice.php';
        }
    }

    /**
     * Declare WooCommerce Blocks compatibility
     */
    public function lkn_wc_editor_blocks_active(): void
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                WC_CIELO_PAYMENT_GATEWAY_FILE,
                true
            );
        }
    }

    /**
     * Register WooCommerce Blocks payment methods
     */
    public function lkn_wc_editor_blocks_add_payment_method(\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void
    {
        if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        $payment_method_registry->register(new Lkn_Wc_Cielo_Credit_Blocks());
        $payment_method_registry->register(new Lkn_Wc_Cielo_Debit_Blocks());
        $payment_method_registry->register(new Lkn_Wc_Cielo_Pix_Blocks());
        $payment_method_registry->register(new Lkn_Wc_Gateway_Cielo_Google_Pay_Blocks());
    }

    /**
     * Show dependency notice
     */
    public function lkn_wc_cielo_dependency_notice(): void
    {
        echo '<div class="notice notice-error"><p>';
        echo __('Payment Gateway for Cielo API on WooCommerce requires WooCommerce to be installed and active.', 'lkn-wc-gateway-cielo');
        echo '</p></div>';
    }


    /**
     * Plugin row meta links.
     *
     * @param array  $plugin_meta an array of the plugin's metadata
     * @param string $plugin_file path to the plugin file, relative to the plugins directory
     *
     * @return array
     */
    public function lkn_wc_cielo_plugin_row_meta($plugin_meta, $plugin_file)
    {
        $new_meta_links['setting'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout'),
            __('Settings', 'lkn-wc-gateway-cielo')
        );

        return array_merge($plugin_meta, $new_meta_links);
    }

    /**
     * Plugin row meta pro link.
     */
    public function lkn_wc_cielo_plugin_row_meta_pro($plugin_meta, $plugin_file)
    {
        // Defina o URL e o texto do link
        $url = 'https://www.linknacional.com.br/wordpress/woocommerce/cielo/';
        $link_text = sprintf(
            '<span style="color: red; font-weight: bold;">%s</span>',
            __('Be pro', 'lkn-wc-gateway-cielo')
        );

        // Crie o novo link de meta
        $new_meta_link = sprintf('<a href="%1$s">%2$s</a>', $url, $link_text);

        // Adicione o novo link ao array de metadados do plugin
        $plugin_meta[] = $new_meta_link;

        return $plugin_meta;
    }

    /**
     * Show the Installments info in Thank you page.
     *
     * @param array $total_rows
     * @param WC_Order $order
     * @param string $tax_display
     */
    public function lkn_new_order_item_totals($total_rows, $order, $tax_display)
    {
        $payment_method = $order->get_payment_method();

        if ($payment_method === 'lkn_cielo_credit' || $payment_method === 'lkn_cielo_debit') {
            $installment = $order->get_meta('installments');
            $payment_id = $order->get_meta('paymentId');
            $order_id = $order->get_id();
            $nsu = $order->get_meta('lkn_nsu');

            // Verifica se é um pagamento Cielo (tem pelo menos payment_id ou nsu)
            $is_cielo_payment = $payment_id || $nsu;

            // Se não é pagamento Cielo, retorna os totais originais
            if (!$is_cielo_payment) {
                return $total_rows;
            }

            // Reconstrói o array com as informações do Cielo
            $payment_method_row = $total_rows['payment_method'] ?? [];
            if (isset($total_rows['payment_method'])) {
                unset($total_rows['payment_method']);
            }
            
            $total_rows['order_id'] = array(
                'label' => __('Order ID', 'lkn-wc-gateway-cielo'),
                'value' => $order_id,
            );
            $total_rows['payment_id'] = array(
                'label' => __('Payment ID', 'lkn-wc-gateway-cielo'),
                'value' => $payment_id ?: 'N/A',
            );
            $total_rows['authorization'] = array(
                'label' => __('Authorization', 'lkn-wc-gateway-cielo'),
                'value' => $nsu ?: 'N/A',
            );
            if ($installment) {
                $total_rows['installment'] = array(
                    'label' => __('Installment', 'lkn-wc-gateway-cielo'),
                    'value' => $installment . 'x',
                );
            }
            $total_rows['payment_method'] = $payment_method_row;
        }

        return $total_rows;
    }
}
