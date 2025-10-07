<?php

namespace Lkn\WcCieloPaymentGateway\Services;

// DEPRECATED: Esta classe foi substituída por SimpleSettingsManager.
// Mantida apenas como stub para evitar erros de autoload em instalações antigas.
// Não deve ser utilizada. Será removida em próxima versão major.
// Toda lógica de configuração agora está em SimpleSettingsManager ou nas próprias classes WooCommerce.*

/**
 * @deprecated 1.26.0 Use SimpleSettingsManager
 */
class SettingsManager
{
    public function __construct()
    {
        // Evita warning caso constante não exista
        $wpDebug = defined('WP_DEBUG') ? constant('WP_DEBUG') : false;
        if ($wpDebug && function_exists('error_log')) {
            error_log('[wc_cielo_payment_gateway] Uso da classe depreciada SettingsManager detectado. Use SimpleSettingsManager.');
        }
    }
}
