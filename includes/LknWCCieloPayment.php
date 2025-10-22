<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloEndpoint;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloCreditBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloDebitBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPixBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloGooglePay;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloGooglePayBlocks;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
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
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
final class LknWCCieloPayment
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LknWcCieloPaymentGatewayLoader    $loader    Maintains and registers all hooks for the plugin.
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
        if (defined('LKN_WC_CIELO_VERSION')) {
            $this->version = LKN_WC_CIELO_VERSION;
        } else {
            $this->version = '1.25.0';
        }
        $this->plugin_name = 'lkn-wc-cielo-payment-gateway';

        $this->load_dependencies();
        $this->loader->add_action('plugins_loaded', $this, 'define_hooks');
    }

    // Gateway classes
    public $lknWcGatewayCieloCredit;
    public $lknWcGatewayCieloDebit;
    public $lknWcCieloPix;
    public $lknWcGatewayCieloGooglePay;
    public $lknWcGatewayCieloEndpoint;
    public $lknWcCieloHelper;

    /**
     * Define os hooks somente quando woocommerce está ativo
     */
    public function define_hooks(): void
    {
        if (class_exists('WooCommerce')) {
            $this->lknWcCieloPix = new LknWcCieloPix();
            $this->define_admin_hooks();
            $this->define_public_hooks();
        } else {
            // Aguarda WooCommerce ser carregado
            $this->loader->add_action('admin_notices', $this, 'woocommerceMissingNotice');
        }
        $this->run();
    }

    public function woocommerceMissingNotice(): void
    {
        deactivate_plugins(plugin_basename(LKN_WC_CIELO_BASE_FILE));
        include_once __DIR__ . '/views/notices/html-notice-woocommerce-missing.php';
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - LknWcCieloPaymentGatewayLoader. Orchestrates the hooks of the plugin.
     * - LknWcCieloPaymentGatewayI18n. Defines internationalization functionality.
     * - LknWcCieloPaymentGatewayAdmin. Defines all hooks for the admin area.
     * - LknWcCieloPaymentGatewayPublic. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies(): void
    {
        $this->loader = new LknWCCieloPaymentLoader();
        $this->lknWcGatewayCieloEndpoint = new LknWcGatewayCieloEndpoint();
        $this->lknWcCieloHelper = new LknWcCieloHelper();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks(): void
    {
        $this->loader->add_action('admin_notices', $this, 'lkn_admin_notice');
        $this->loader->add_filter('plugin_action_links_' . LKN_WC_CIELO_FILE_BASENAME, $this, 'lkn_wc_cielo_plugin_row_meta', 10, 2);
        $this->loader->add_filter('plugin_action_links_' . LKN_WC_CIELO_FILE_BASENAME, $this, 'lkn_wc_cielo_plugin_row_meta_pro', 10, 2);
        $this->loader->add_action('lkn_schedule_check_free_pix_payment_hook', LknWcCieloRequest::class, 'check_payment', 10, 2);
        // Admin settings card for specific sections
        $this->setup_admin_settings_card();
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks(): void
    {
        $this->loader->add_filter('woocommerce_payment_gateways', $this, 'add_gateway');
        $this->loader->add_action('rest_api_init', $this->lknWcGatewayCieloEndpoint, 'registerOrderCaptureEndPoint');
        $this->loader->add_action('add_meta_boxes', $this->lknWcCieloHelper, 'showOrderLogs');
        $this->loader->add_action('woocommerce_order_details_after_order_table', $this->lknWcCieloPix, 'showPix');
        $this->loader->add_filter('woocommerce_get_order_item_totals', $this, 'new_order_item_totals', 10, 3);
        // WooCommerce Blocks compatibility
        $this->loader->add_action('before_woocommerce_init', $this, 'wcEditorBlocksActive');
        $this->loader->add_action('woocommerce_blocks_payment_method_type_registration', $this, 'wcEditorBlocksAddPaymentMethod');
    }

    /**
     * Setup admin settings card for specific gateway sections
     *
     * @since    1.0.0
     * @access   private
     */
    private function setup_admin_settings_card(): void
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
            $this->loader->add_action('woocommerce_init', $this, 'load_admin_settings_assets');
        }
    }

    /**
     * Load admin settings assets
     *
     * @since    1.0.0
     */
    public function load_admin_settings_assets(): void
    {
        $versions = 'Plugin Cielo v' . LKN_WC_CIELO_VERSION;
        if (defined('LKN_CIELO_API_PRO_VERSION')) {
            $versions .= ' | Cielo Pro v' . LKN_CIELO_API_PRO_VERSION;
        } else {
            $versions .= ' | WooCommerce v' . WC()->version;
        }

        wp_enqueue_script('lknCieloForWoocommerceCard', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/js/admin/lkn-woocommerce-admin-card.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
        wp_enqueue_style('lknCieloForWoocommerceCard', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/css/frontend/lkn-woocommerce-admin-card.css', array(), LKN_WC_CIELO_VERSION, 'all');
        wp_enqueue_script('lknCieloForWoocommerceProSettings', LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/js/admin/lkn-settings-pro-fields.js', array(), LKN_WC_CIELO_VERSION, false);

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
                    'right' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/backgroundCardRight.svg',
                    'left' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/backgroundCardLeft.svg'
                ),
                'logo' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/linkNacionalLogo.webp',
                'whatsapp' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/whatsapp-icon.svg',
                'telegram' => LKN_WC_GATEWAY_CIELO_DIR_URL . 'resources/img/telegram-icon.svg',
                'versions' => $versions
            ),
            'woocommerce/adminSettingsCard/',
            LKN_WC_GATEWAY_CIELO_DIR . '/includes/templates/'
        );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run(): void
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    LknWcCieloPaymentGatewayLoader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array $gateways
     */
    public function add_gateway($methods)
    {
        $lknWcGatewayCieloCreditClass = new LknWcGatewayCieloCredit();
        $lknWcGatewayCieloDebitClass = new LknWcGatewayCieloDebit();
        $lknWcGatewayCieloPixClass = new LknWcCieloPix();
        $lknWcGatewayCieloGooglePayClass = new LknWcGatewayCieloGooglePay();

        array_push($methods, $lknWcGatewayCieloCreditClass);
        array_push($methods, $lknWcGatewayCieloDebitClass);
        array_push($methods, $lknWcGatewayCieloPixClass);
        array_push($methods, $lknWcGatewayCieloGooglePayClass);

        return $methods;
    }

    /**
     * Show admin notice for fraud detection plugin
     */
    public function lkn_admin_notice(): void
    {
        if (!file_exists(WP_PLUGIN_DIR . '/fraud-detection-for-woocommerce/fraud-detection-for-woocommerce.php') && (!is_plugin_active('integration-rede-for-woocommerce/integration-rede-for-woocommerce.php') && !is_plugin_active('woo-rede/integration-rede-for-woocommerce.php'))) {
            require LKN_WC_GATEWAY_CIELO_DIR . 'includes/views/notices/LknWcCieloDownloadNotice.php';
        }
    }

    /**
     * Declare WooCommerce Blocks compatibility
     */
    public function wcEditorBlocksActive(): void
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                LKN_WC_CIELO_FILE,
                true
            );
        }
    }

    /**
     * Register WooCommerce Blocks payment methods
     */
    public function wcEditorBlocksAddPaymentMethod(\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void
    {
        if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        $payment_method_registry->register(new LknWcCieloCreditBlocks());
        $payment_method_registry->register(new LknWcCieloDebitBlocks());
        $payment_method_registry->register(new LknWcCieloPixBlocks());
        $payment_method_registry->register(new LknWcGatewayCieloGooglePayBlocks());
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
    public function new_order_item_totals($total_rows, $order, $tax_display)
    {
        $payment_method = $order->get_payment_method();

        if ($payment_method === 'lkn_cielo_credit' || $payment_method === 'lkn_cielo_debit') {
            $installment = $order->get_meta('installments');
            $payment_id = $order->get_meta('paymentId');
            $order_id = $order->get_id();
            $nsu = $order->get_meta('lkn_nsu');
            $interest_amount = $order->get_meta('interest_amount');

            // Verifica se é um pagamento Cielo (tem pelo menos payment_id ou nsu)
            $is_cielo_payment = $payment_id || $nsu;

            // Se não é pagamento Cielo, retorna os totais originais
            if (!$is_cielo_payment) {
                return $total_rows;
            }

            // Reconstrói o array com as informações do Cielo
            $order_total_row = $total_rows['order_total'] ?? [];
            $payment_method_row = $total_rows['payment_method'] ?? [];
            if (isset($total_rows['order_total'])) {
                unset($total_rows['order_total']);
            }

            if (isset($total_rows['payment_method'])) {
                unset($total_rows['payment_method']);
            }

            if ($interest_amount) {
                $label_positive = esc_html__('Installment Interest:', 'lkn-wc-gateway-cielo');
                $label_negative = esc_html__('Installment Discount:', 'lkn-wc-gateway-cielo');
                $interest_label = $interest_amount > 0 ? $label_positive : $label_negative;
                $total_rows['interest'] = array(
                    'label' => $interest_label,
                    'value' => wc_price($interest_amount),
                );
            }
            if (!empty($order_total_row)) {
                $total_rows['order_total'] = $order_total_row;
            }

            $total_rows['order_id'] = array(
                'label' => esc_html__('Order ID', 'lkn-wc-gateway-cielo'),
                'value' => $order_id,
            );
            if ($installment) {
                $valorParcela = number_format(($order->get_total() / $installment), 2, ',', '.');
                $total_rows['installment'] = array(
                    'label' => esc_html__('Installment', 'lkn-wc-gateway-cielo'),
                    'value' => $installment . 'x de R$ ' . $valorParcela
                );
            }
            $total_rows['payment_id'] = array(
                'label' => esc_html__('Payment ID', 'lkn-wc-gateway-cielo'),
                'value' => $payment_id ?: 'N/A',
            );
            $total_rows['authorization'] = array(
                'label' => esc_html__('Authorization', 'lkn-wc-gateway-cielo'),
                'value' => $nsu ?: 'N/A',
            );
            $total_rows['payment_method'] = $payment_method_row;
        }

        return $total_rows;
    }
}
