<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

/**
 * Fired during plugin activation
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
final class LknWCCieloPaymentActivator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate(): void
    {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
