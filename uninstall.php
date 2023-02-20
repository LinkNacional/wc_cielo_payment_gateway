<?php

/**
 * Uninstall Lkn_WC_Cielo_Payment.
 *
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 *
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clear WooCommerce Gateway options
delete_option('woocommerce_lkn_cielo_credit_settings');
delete_option('woocommerce_lkn_cielo_debit_settings');
