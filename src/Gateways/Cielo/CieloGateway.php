<?php

namespace Lkn\WcCieloPaymentGateway\Gateways\Cielo;

use Lkn\WcCieloPaymentGateway\Gateways\AbstractApiGateway;
use Lkn\WcCieloPaymentGateway\Exceptions\PaymentException;
use Lkn\WcCieloPaymentGateway\Exceptions\ValidationException;

/**
 * Cielo API Gateway implementation
 *
 * @since 1.25.0
 */
class CieloGateway extends AbstractApiGateway
{
    /**
     * Process a payment transaction
     *
     * @param array $paymentData Payment data
     * @return array Response data from the API
     * @throws PaymentException
     */
    public function processPayment(array $paymentData): array
    {
        $this->validatePaymentData($paymentData);

        $requestData = $this->buildPaymentRequest($paymentData);
        
        return $this->makeApiRequest('POST', '1/sales/', $requestData);
    }

    /**
     * Create a PIX transaction
     *
     * @param array $pixData PIX transaction data
     * @return array PIX response with QR code and other data
     * @throws PaymentException
     */
    public function createPixTransaction(array $pixData): array
    {
        $this->validatePaymentData($pixData);

        $requestData = $this->buildPixRequest($pixData);
        
        return $this->makeApiRequest('POST', '1/sales/', $requestData);
    }

    /**
     * Process credit card payment
     *
     * @param array $creditData Credit card payment data
     * @return array Credit card payment response
     * @throws PaymentException
     */
    public function processCreditPayment(array $creditData): array
    {
        $this->validateCreditCardData($creditData);

        $requestData = $this->buildCreditCardRequest($creditData);
        
        return $this->makeApiRequest('POST', '1/sales/', $requestData);
    }

    /**
     * Process debit card payment
     *
     * @param array $debitData Debit card payment data
     * @return array Debit card payment response
     * @throws PaymentException
     */
    public function processDebitPayment(array $debitData): array
    {
        $this->validateDebitCardData($debitData);

        $requestData = $this->buildDebitCardRequest($debitData);
        
        return $this->makeApiRequest('POST', '1/sales/', $requestData);
    }

    /**
     * Cancel/reverse a payment
     *
     * @param string $paymentId Payment ID to be cancelled
     * @param float|null $amount Amount to cancel (partial cancellation)
     * @return array Cancellation response
     * @throws PaymentException
     */
    public function cancelPayment(string $paymentId, ?float $amount = null): array
    {
        $endpoint = "1/sales/{$paymentId}/void";
        
        $requestData = [];
        if ($amount !== null) {
            $requestData['Amount'] = $this->formatAmount($amount);
        }

        return $this->makeApiRequest('PUT', $endpoint, $requestData);
    }

    /**
     * Capture a payment (for authorized payments)
     *
     * @param string $paymentId Payment ID to be captured
     * @param float|null $amount Amount to capture (partial capture)
     * @return array Capture response
     * @throws PaymentException
     */
    public function capturePayment(string $paymentId, ?float $amount = null): array
    {
        $endpoint = "1/sales/{$paymentId}/capture";
        
        $requestData = [];
        if ($amount !== null) {
            $requestData['Amount'] = $this->formatAmount($amount);
        }

        return $this->makeApiRequest('PUT', $endpoint, $requestData);
    }

    /**
     * Check payment status
     *
     * @param string $paymentId Payment ID to check
     * @return array Payment status response
     * @throws PaymentException
     */
    public function getPaymentStatus(string $paymentId): array
    {
        return $this->makeApiRequest('GET', "1/sales/{$paymentId}");
    }

    /**
     * Process webhook notification
     *
     * @param array $webhookData Webhook payload data
     * @return array Processing result
     * @throws PaymentException
     */
    public function processWebhook(array $webhookData): array
    {
        // Webhook processing is handled by WebhookRouter service
        // This method exists to satisfy the interface
        return ['status' => 'success', 'message' => 'Webhook processed'];
    }

    /**
     * Build general payment request
     *
     * @param array $data Payment data
     * @return array Request data
     */
    private function buildPaymentRequest(array $data): array
    {
        $request = [
            'MerchantOrderId' => $data['order_id'],
            'Customer' => $this->buildCustomerData($data['customer'] ?? []),
            'Payment' => [
                'Type' => $data['payment_type'] ?? 'CreditCard',
                'Amount' => $this->formatAmount($data['amount']),
                'Currency' => $data['currency'] ?? 'BRL',
                'Country' => $data['country'] ?? 'BRA',
                'Installments' => $data['installments'] ?? 1,
                'SoftDescriptor' => $data['soft_descriptor'] ?? $this->config['soft_descriptor'] ?? '',
                'Capture' => $data['capture'] ?? $this->config['capture'] ?? true
            ]
        ];

        return $request;
    }

    /**
     * Build PIX request
     *
     * @param array $data PIX data
     * @return array Request data
     */
    private function buildPixRequest(array $data): array
    {
        $request = $this->buildPaymentRequest($data);
        
        $request['Payment']['Type'] = 'Pix';
        $request['Payment']['ExpirationDate'] = $this->calculatePixExpiration($data['expiration_minutes'] ?? 15);
        
        // Remove installments for PIX
        unset($request['Payment']['Installments']);

        return $request;
    }

    /**
     * Build credit card request
     *
     * @param array $data Credit card data
     * @return array Request data
     */
    private function buildCreditCardRequest(array $data): array
    {
        $request = $this->buildPaymentRequest($data);
        
        $request['Payment']['Type'] = 'CreditCard';
        $request['Payment']['CreditCard'] = [
            'CardNumber' => $data['card_number'],
            'Holder' => $data['card_holder'],
            'ExpirationDate' => $data['card_expiry'],
            'SecurityCode' => $data['card_cvv'],
            'Brand' => $data['card_brand'] ?? ''
        ];

        return $request;
    }

    /**
     * Build debit card request
     *
     * @param array $data Debit card data
     * @return array Request data
     */
    private function buildDebitCardRequest(array $data): array
    {
        $request = $this->buildPaymentRequest($data);
        
        $request['Payment']['Type'] = 'DebitCard';
        $request['Payment']['DebitCard'] = [
            'CardNumber' => $data['card_number'],
            'Holder' => $data['card_holder'],
            'ExpirationDate' => $data['card_expiry'],
            'SecurityCode' => $data['card_cvv'],
            'Brand' => $data['card_brand'] ?? ''
        ];
        
        // Debit cards don't support installments
        $request['Payment']['Installments'] = 1;

        return $request;
    }

    /**
     * Build customer data
     *
     * @param array $customer Customer data
     * @return array Formatted customer data
     */
    private function buildCustomerData(array $customer): array
    {
        return [
            'Name' => $customer['name'] ?? '',
            'Email' => $customer['email'] ?? '',
            'Birthdate' => $customer['birthdate'] ?? '',
            'Address' => $this->buildAddressData($customer['address'] ?? []),
            'DeliveryAddress' => $this->buildAddressData($customer['delivery_address'] ?? $customer['address'] ?? [])
        ];
    }

    /**
     * Build address data
     *
     * @param array $address Address data
     * @return array Formatted address data
     */
    private function buildAddressData(array $address): array
    {
        return [
            'Street' => $address['street'] ?? '',
            'Number' => $address['number'] ?? '',
            'Complement' => $address['complement'] ?? '',
            'ZipCode' => $address['zipcode'] ?? '',
            'City' => $address['city'] ?? '',
            'State' => $address['state'] ?? '',
            'Country' => $address['country'] ?? 'BRA'
        ];
    }

    /**
     * Calculate PIX expiration date
     *
     * @param int $minutes Minutes until expiration
     * @return string ISO 8601 formatted date
     */
    private function calculatePixExpiration(int $minutes): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', time() + ($minutes * 60));
    }

    /**
     * Validate credit card specific data
     *
     * @param array $data Credit card data
     * @throws ValidationException
     */
    private function validateCreditCardData(array $data): void
    {
        $this->validatePaymentData($data);

        $required = ['card_number', 'card_holder', 'card_expiry', 'card_cvv'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new ValidationException("Required credit card field missing: {$field}");
            }
        }
    }

    /**
     * Validate debit card specific data
     *
     * @param array $data Debit card data
     * @throws ValidationException
     */
    private function validateDebitCardData(array $data): void
    {
        // Same validation as credit card
        $this->validateCreditCardData($data);
    }

    /**
     * Processa um pagamento com Google Pay (implementação da interface)
     *
     * @param array $google_pay_data Dados do Google Pay
     * @return array Resposta do processamento do Google Pay
     * @throws PaymentException
     */
    public function processGooglePayPayment(array $google_pay_data): array
    {
        $this->validatePaymentData($google_pay_data);

        $requestData = $this->buildGooglePayRequest($google_pay_data);
        
        return $this->makeApiRequest('POST', '1/sales/', $requestData);
    }

    /**
     * Consulta o status de uma transação (implementação da interface)
     *
     * @param string $transaction_id ID da transação
     * @return array Status da transação
     * @throws PaymentException
     */
    public function getTransactionStatus(string $transaction_id): array
    {
        return $this->getPaymentStatus($transaction_id);
    }

    /**
     * Cancela uma transação (implementação da interface)
     *
     * @param string $transaction_id ID da transação
     * @return array Resposta do cancelamento
     * @throws PaymentException
     */
    public function cancelTransaction(string $transaction_id): array
    {
        return $this->cancelPayment($transaction_id);
    }

    /**
     * Captura uma transação pré-autorizada (implementação da interface)
     *
     * @param string $transaction_id ID da transação
     * @param float $amount Valor a ser capturado
     * @return array Resposta da captura
     * @throws PaymentException
     */
    public function captureTransaction(string $transaction_id, float $amount): array
    {
        return $this->capturePayment($transaction_id, $amount);
    }

    /**
     * Obtém as configurações necessárias para o gateway (implementação da interface)
     *
     * @return array Array com as configurações obrigatórias
     */
    public function getRequiredSettings(): array
    {
        return [
            'merchant_id' => 'ID do Merchant Cielo',
            'merchant_key' => 'Chave do Merchant Cielo',
            'environment' => 'Ambiente (sandbox/production)',
            'soft_descriptor' => 'Descrição na Fatura'
        ];
    }

    /**
     * Constrói request para Google Pay
     *
     * @param array $data Dados do Google Pay
     * @return array Request data
     */
    private function buildGooglePayRequest(array $data): array
    {
        return [
            'MerchantOrderId' => $data['order_id'],
            'Customer' => $this->buildCustomerData($data['customer'] ?? []),
            'Payment' => [
                'Type' => 'DebitCard',
                'Amount' => $this->formatAmount($data['amount']),
                'Currency' => $data['currency'] ?? 'BRL',
                'DebitCard' => [
                    'PaymentToken' => $data['payment_token'] ?? '',
                    'Brand' => $data['brand'] ?? 'Visa'
                ]
            ]
        ];
    }
}
