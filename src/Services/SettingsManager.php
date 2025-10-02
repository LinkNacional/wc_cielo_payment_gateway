<?php

namespace Lkn\WcCieloPaymentGateway\Services;

/**
 * Settings Manager service
 *
 * @since 1.25.0
 */
class SettingsManager
{
    /**
     * Get gateway field configurations for all Cielo payment methods
     *
     * @return array Array of field configurations
     */
    public function getGatewayFields(): array
    {
        return [
            'credit' => $this->getCreditCardFields(),
            'debit' => $this->getDebitCardFields(),
            'pix' => $this->getPixFields(),
            'google_pay' => $this->getGooglePayFields()
        ];
    }

    /**
     * Get credit card specific fields
     *
     * @return array
     */
    public function getCreditCardFields(): array
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Cielo Credit Card', 'lkn-wc-gateway-cielo'),
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Credit Card', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Pay with your credit card via Cielo.', 'lkn-wc-gateway-cielo'),
            ],
            'merchant_id' => [
                'title' => __('Merchant ID', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Please enter your Cielo Merchant ID.', 'lkn-wc-gateway-cielo'),
                'default' => '',
                'desc_tip' => true,
            ],
            'merchant_key' => [
                'title' => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Please enter your Cielo Merchant Key.', 'lkn-wc-gateway-cielo'),
                'default' => '',
                'desc_tip' => true,
            ],
            'environment' => [
                'title' => __('Environment', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'description' => __('Choose between sandbox and production environment.', 'lkn-wc-gateway-cielo'),
                'default' => 'sandbox',
                'desc_tip' => true,
                'options' => [
                    'sandbox' => __('Sandbox', 'lkn-wc-gateway-cielo'),
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                ]
            ],
            'capture' => [
                'title' => __('Capture', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Capture payment immediately', 'lkn-wc-gateway-cielo'),
                'description' => __('When enabled, payment will be captured immediately. When disabled, payment will only be authorized.', 'lkn-wc-gateway-cielo'),
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'installments' => [
                'title' => __('Maximum Installments', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'description' => __('Maximum number of installments allowed.', 'lkn-wc-gateway-cielo'),
                'default' => '12',
                'desc_tip' => true,
                'options' => array_combine(range(1, 12), range(1, 12))
            ],
            'soft_descriptor' => [
                'title' => __('Soft Descriptor', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Text that will be displayed on cardholder\'s invoice.', 'lkn-wc-gateway-cielo'),
                'default' => '',
                'desc_tip' => true,
                'custom_attributes' => [
                    'maxlength' => '13'
                ]
            ],
            'debug' => [
                'title' => __('Debug Log', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Log Cielo API events, such as requests and responses.', 'lkn-wc-gateway-cielo'),
            ]
        ];
    }

    /**
     * Get debit card specific fields
     *
     * @return array
     */
    public function getDebitCardFields(): array
    {
        $fields = $this->getCreditCardFields();
        
        // Remove installments option for debit
        unset($fields['installments']);
        
        // Update title and description defaults
        $fields['title']['default'] = __('Debit Card', 'lkn-wc-gateway-cielo');
        $fields['description']['default'] = __('Pay with your debit card via Cielo.', 'lkn-wc-gateway-cielo');
        $fields['enabled']['label'] = __('Enable Cielo Debit Card', 'lkn-wc-gateway-cielo');

        return $fields;
    }

    /**
     * Get PIX specific fields
     *
     * @return array
     */
    public function getPixFields(): array
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Cielo PIX', 'lkn-wc-gateway-cielo'),
                'default' => 'no'
            ],
            'title' => [
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('PIX', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Pay instantly with PIX via Cielo.', 'lkn-wc-gateway-cielo'),
            ],
            'merchant_id' => [
                'title' => __('Merchant ID', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Please enter your Cielo Merchant ID.', 'lkn-wc-gateway-cielo'),
                'default' => '',
                'desc_tip' => true,
            ],
            'merchant_key' => [
                'title' => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Please enter your Cielo Merchant Key.', 'lkn-wc-gateway-cielo'),
                'default' => '',
                'desc_tip' => true,
            ],
            'environment' => [
                'title' => __('Environment', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'description' => __('Choose between sandbox and production environment.', 'lkn-wc-gateway-cielo'),
                'default' => 'sandbox',
                'desc_tip' => true,
                'options' => [
                    'sandbox' => __('Sandbox', 'lkn-wc-gateway-cielo'),
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                ]
            ],
            'pix_expiration' => [
                'title' => __('PIX Expiration Time (minutes)', 'lkn-wc-gateway-cielo'),
                'type' => 'number',
                'description' => __('Time in minutes for PIX QR code expiration.', 'lkn-wc-gateway-cielo'),
                'default' => '15',
                'desc_tip' => true,
                'custom_attributes' => [
                    'min' => '5',
                    'max' => '1440',
                    'step' => '1'
                ]
            ],
            'soft_descriptor' => [
                'title' => __('Soft Descriptor', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Text that will be displayed on the PIX transaction.', 'lkn-wc-gateway-cielo'),
                'default' => '',
                'desc_tip' => true,
                'custom_attributes' => [
                    'maxlength' => '13'
                ]
            ],
            'debug' => [
                'title' => __('Debug Log', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Log Cielo API events, such as requests and responses.', 'lkn-wc-gateway-cielo'),
            ]
        ];
    }

    /**
     * Get Google Pay specific fields
     *
     * @return array
     */
    public function getGooglePayFields(): array
    {
        $fields = $this->getCreditCardFields();
        
        // Update title and description defaults
        $fields['title']['default'] = __('Google Pay', 'lkn-wc-gateway-cielo');
        $fields['description']['default'] = __('Pay with Google Pay via Cielo.', 'lkn-wc-gateway-cielo');
        $fields['enabled']['label'] = __('Enable Cielo Google Pay', 'lkn-wc-gateway-cielo');

        return $fields;
    }

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
}
