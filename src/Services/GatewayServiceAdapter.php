<?php

namespace Lkn\WcCieloPaymentGateway\Services;

/**
 * Gateway Service Adapter
 * 
 * Adapts the new service architecture to work with existing WooCommerce gateway classes
 *
 * @since 1.25.0
 */
class GatewayServiceAdapter
{
    /**
     * Service Container
     *
     * @var ServiceContainer
     */
    private static $serviceContainer;

    /**
     * Set the service container
     *
     * @param ServiceContainer $serviceContainer
     */
    public static function setServiceContainer(ServiceContainer $serviceContainer): void
    {
        self::$serviceContainer = $serviceContainer;
    }

    /**
     * Get the service container
     *
     * @return ServiceContainer|null
     */
    public static function getServiceContainer(): ?ServiceContainer
    {
        return self::$serviceContainer;
    }

    /**
     * Get a service from the container
     *
     * @param string $serviceName
     * @return mixed
     */
    public static function getService(string $serviceName)
    {
        if (self::$serviceContainer && self::$serviceContainer->has($serviceName)) {
            return self::$serviceContainer->get($serviceName);
        }

        return null;
    }

    /**
     * Get Cielo Gateway with configuration from WooCommerce gateway instance
     *
     * @param object $wcGateway WooCommerce gateway instance
     * @return \Lkn\WcCieloPaymentGateway\Gateways\Cielo\CieloGateway|null
     */
    public static function getCieloGateway($wcGateway)
    {
        $settingsManager = self::getService('settingsManager');
        $cieloGateway = self::getService('cieloGateway');

        if (!$settingsManager || !$cieloGateway) {
            return null;
        }

        // Get configuration from WooCommerce gateway
        $config = $settingsManager->getMerchantSettings($wcGateway);

        // Create a new instance with the configuration
        $httpClient = self::getService('httpClient');
        $cieloGatewayClass = get_class($cieloGateway);
        
        return new $cieloGatewayClass($httpClient, $settingsManager, $config);
    }

    /**
     * Process PIX payment using new architecture
     *
     * @param object $wcGateway WooCommerce gateway instance
     * @param \WC_Order $order WooCommerce order
     * @param array $billingData Billing data including CPF/CNPJ
     * @return array API response
     */
    public static function processPixPayment($wcGateway, $order, array $billingData): array
    {
        $cieloGateway = self::getCieloGateway($wcGateway);
        
        if (!$cieloGateway) {
            throw new \Exception('Cielo Gateway service not available');
        }

        $pixData = [
            'amount' => (float) $order->get_total(),
            'order_id' => $order->get_id(),
            'currency' => $order->get_currency(),
            'expiration_minutes' => (int) $wcGateway->get_option('pix_expiration', 15),
            'soft_descriptor' => $wcGateway->get_option('soft_descriptor'),
            'customer' => [
                'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'email' => $order->get_billing_email(),
                'document' => $billingData['Identity'],
                'document_type' => $billingData['IdentityType'],
                'address' => [
                    'street' => $order->get_billing_address_1(),
                    'number' => '',
                    'complement' => $order->get_billing_address_2(),
                    'zipcode' => $order->get_billing_postcode(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'country' => $order->get_billing_country()
                ]
            ]
        ];

        return $cieloGateway->createPixTransaction($pixData);
    }

    /**
     * Process credit card payment using new architecture
     *
     * @param object $wcGateway WooCommerce gateway instance
     * @param \WC_Order $order WooCommerce order
     * @param array $cardData Credit card data
     * @return array API response
     */
    public static function processCreditPayment($wcGateway, $order, array $cardData): array
    {
        $cieloGateway = self::getCieloGateway($wcGateway);
        
        if (!$cieloGateway) {
            throw new \Exception('Cielo Gateway service not available');
        }

        $creditData = [
            'amount' => (float) $order->get_total(),
            'order_id' => $order->get_id(),
            'currency' => $order->get_currency(),
            'installments' => $cardData['installments'] ?? 1,
            'capture' => $wcGateway->get_option('capture', 'yes') === 'yes',
            'soft_descriptor' => $wcGateway->get_option('soft_descriptor'),
            'card_number' => $cardData['card_number'],
            'card_holder' => $cardData['card_holder'],
            'card_expiry' => $cardData['card_expiry'],
            'card_cvv' => $cardData['card_cvv'],
            'card_brand' => $cardData['card_brand'] ?? '',
            'customer' => [
                'name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'email' => $order->get_billing_email(),
                'address' => [
                    'street' => $order->get_billing_address_1(),
                    'number' => '',
                    'complement' => $order->get_billing_address_2(),
                    'zipcode' => $order->get_billing_postcode(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'country' => $order->get_billing_country()
                ]
            ]
        ];

        return $cieloGateway->processCreditPayment($creditData);
    }

    /**
     * Check payment status
     *
     * @param object $wcGateway WooCommerce gateway instance
     * @param string $paymentId Cielo payment ID
     * @return array Payment status response
     */
    public static function checkPaymentStatus($wcGateway, string $paymentId): array
    {
        $cieloGateway = self::getCieloGateway($wcGateway);
        
        if (!$cieloGateway) {
            throw new \Exception('Cielo Gateway service not available');
        }

        return $cieloGateway->getPaymentStatus($paymentId);
    }
}
