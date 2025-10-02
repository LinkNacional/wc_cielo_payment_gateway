<?php

namespace Lkn\WcCieloPaymentGateway\Services;

/**
 * Settings Manager service
 *
 * @since 1.25.0
 */
class SimpleSettingsManager
{
    /**
     * Get environment URLs
     *
     * @param string $environment 'sandbox' or 'production'
     * @return array Array with API URLs
     */
    public function getEnvironmentUrls(string $environment): array
    {
        if ($environment === 'production') {
            return [
                'api_url' => 'https://api.cieloecommerce.cielo.com.br/',
                'api_query_url' => 'https://apiquery.cieloecommerce.cielo.com.br/'
            ];
        }

        return [
            'api_url' => 'https://apisandbox.cieloecommerce.cielo.com.br/',
            'api_query_url' => 'https://apiquerysandbox.cieloecommerce.cielo.com.br/'
        ];
    }

    /**
     * Validate gateway configuration
     *
     * @param array $settings Gateway settings
     * @return bool True if valid configuration
     */
    public function validateGatewaySettings(array $settings): bool
    {
        $requiredFields = ['merchant_id', 'merchant_key'];

        foreach ($requiredFields as $field) {
            if (empty($settings[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get merchant settings from gateway instance
     *
     * @param object $gateway Gateway instance
     * @return array Settings array
     */
    public function getMerchantSettings($gateway): array
    {
        return [
            'merchant_id' => $gateway->get_option('merchant_id'),
            'merchant_key' => $gateway->get_option('merchant_key'),
            'environment' => $gateway->get_option('environment', 'sandbox'),
            'capture' => $gateway->get_option('capture', 'yes') === 'yes',
            'soft_descriptor' => $gateway->get_option('soft_descriptor'),
            'debug' => $gateway->get_option('debug', 'no') === 'yes'
        ];
    }
}
