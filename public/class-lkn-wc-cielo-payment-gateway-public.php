<?php

namespace Lkn\WcCieloPaymentGateway\PublicView;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknWcCieloPaymentGateway
 * @subpackage LknWcCieloPaymentGateway/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    LknWcCieloPaymentGateway
 * @subpackage LknWcCieloPaymentGateway/public
 * @author     Link Nacional <contato@linknacional.com>
 */
final class Lkn_Wc_Cielo_Payment_Gateway_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function lkn_enqueue_styles(): void {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lkn_Wc_Cielo_Payment_Gateway_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lkn_Wc_Cielo_Payment_Gateway_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style( $this->plugin_name, WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'public/css/lkn-wc-cielo-payment-gateway-public.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function lkn_enqueue_scripts(): void {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Lkn_Wc_Cielo_Payment_Gateway_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Lkn_Wc_Cielo_Payment_Gateway_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script( $this->plugin_name, WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'public/js/lkn-wc-cielo-payment-gateway-public.js', array( 'jquery' ), $this->version, false );

    }

}
