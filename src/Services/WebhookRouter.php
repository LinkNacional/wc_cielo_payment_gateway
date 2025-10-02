<?php

namespace Lkn\WcCieloPaymentGateway\Services;

use Lkn\WcCieloPaymentGateway\Exceptions\PaymentException;

/**
 * Webhook Router service for handling Cielo webhooks
 *
 * @since 1.25.0
 */
class WebhookRouter
{
    /**
     * Process incoming webhook
     *
     * @param array $webhookData Raw webhook payload
     * @return array Processing result
     * @throws PaymentException
     */
    public function processWebhook(array $webhookData): array
    {
        // Validate webhook data structure
        if (!$this->validateWebhookStructure($webhookData)) {
            throw new PaymentException('Invalid webhook data structure');
        }

        // Extract payment ID and event type
        $paymentId = $webhookData['PaymentId'] ?? null;
        $changeType = $webhookData['ChangeType'] ?? null;

        if (!$paymentId || !$changeType) {
            throw new PaymentException('Missing required webhook fields');
        }

        // Route to appropriate handler based on change type
        switch ($changeType) {
            case '1': // Payment confirmed
                return $this->handlePaymentConfirmed($paymentId, $webhookData);
            
            case '2': // Payment cancelled
                return $this->handlePaymentCancelled($paymentId, $webhookData);
            
            case '3': // Payment denied
                return $this->handlePaymentDenied($paymentId, $webhookData);
            
            case '4': // Payment authorized
                return $this->handlePaymentAuthorized($paymentId, $webhookData);
            
            default:
                throw new PaymentException("Unknown webhook change type: {$changeType}");
        }
    }

    /**
     * Validate webhook data structure
     *
     * @param array $data Webhook data
     * @return bool
     */
    private function validateWebhookStructure(array $data): bool
    {
        $requiredFields = ['PaymentId', 'ChangeType'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Handle payment confirmed webhook
     *
     * @param string $paymentId Payment ID
     * @param array $data Webhook data
     * @return array
     */
    private function handlePaymentConfirmed(string $paymentId, array $data): array
    {
        // Find order by payment ID
        $order = $this->findOrderByPaymentId($paymentId);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }

        // Mark order as paid if not already
        if (!$order->is_paid()) {
            $order->payment_complete($paymentId);
            $order->add_order_note('Payment confirmed via Cielo webhook.');
        }

        return ['status' => 'success', 'message' => 'Payment confirmed'];
    }

    /**
     * Handle payment cancelled webhook
     *
     * @param string $paymentId Payment ID
     * @param array $data Webhook data
     * @return array
     */
    private function handlePaymentCancelled(string $paymentId, array $data): array
    {
        $order = $this->findOrderByPaymentId($paymentId);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }

        // Update order status to cancelled
        if ($order->get_status() !== 'cancelled') {
            $order->update_status('cancelled', 'Payment cancelled via Cielo webhook.');
        }

        return ['status' => 'success', 'message' => 'Payment cancelled'];
    }

    /**
     * Handle payment denied webhook
     *
     * @param string $paymentId Payment ID
     * @param array $data Webhook data
     * @return array
     */
    private function handlePaymentDenied(string $paymentId, array $data): array
    {
        $order = $this->findOrderByPaymentId($paymentId);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }

        // Update order status to failed
        if ($order->get_status() !== 'failed') {
            $order->update_status('failed', 'Payment denied via Cielo webhook.');
        }

        return ['status' => 'success', 'message' => 'Payment denied'];
    }

    /**
     * Handle payment authorized webhook
     *
     * @param string $paymentId Payment ID
     * @param array $data Webhook data
     * @return array
     */
    private function handlePaymentAuthorized(string $paymentId, array $data): array
    {
        $order = $this->findOrderByPaymentId($paymentId);
        
        if (!$order) {
            return ['status' => 'error', 'message' => 'Order not found'];
        }

        // Update order status to on-hold for authorized payments
        if ($order->get_status() !== 'on-hold') {
            $order->update_status('on-hold', 'Payment authorized via Cielo webhook.');
        }

        return ['status' => 'success', 'message' => 'Payment authorized'];
    }

    /**
     * Find WooCommerce order by Cielo payment ID
     *
     * @param string $paymentId Cielo payment ID
     * @return \WC_Order|null
     */
    private function findOrderByPaymentId(string $paymentId): ?\WC_Order
    {
        // Search for orders with this payment ID in meta
        $orders = wc_get_orders([
            'meta_query' => [
                [
                    'key' => 'paymentId',
                    'value' => $paymentId,
                    'compare' => '='
                ]
            ],
            'limit' => 1
        ]);

        return !empty($orders) ? $orders[0] : null;
    }
}
