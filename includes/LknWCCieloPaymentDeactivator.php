<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

/**
 * Fired during plugin deactivation
 *
 * @link       https://linknacional.com.br
 * @since      1.0.0
 *
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    LknWCCieloPaymentGateway
 * @subpackage LknWCCieloPaymentGateway/includes
 * @author     Link Nacional <contato@linknacional.com>
 */
final class LknWCCieloPaymentDeactivator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate(): void
    {
        // Clear any scheduled hooks
        wp_clear_scheduled_hook('lkn_schedule_check_free_pix_payment_hook');
    }
}
