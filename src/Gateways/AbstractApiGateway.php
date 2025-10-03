<?php

namespace Lkn\WcCieloPaymentGateway\Gateways;

use Lkn\WcCieloPaymentGateway\Contracts\PaymentApiInterface;
use Lkn\WcCieloPaymentGateway\Services\HttpClient;
use Lkn\WcCieloPaymentGateway\Services\SimpleSettingsManager;
use Lkn\WcCieloPaymentGateway\Exceptions\PaymentException;
use Lkn\WcCieloPaymentGateway\Exceptions\AuthenticationException;
use Lkn\WcCieloPaymentGateway\Exceptions\NetworkException;
use Lkn\WcCieloPaymentGateway\Exceptions\ValidationException;

/**
 * Abstract base gateway for Cielo API implementations
 *
 * @since 1.25.0
 */
abstract class AbstractApiGateway implements PaymentApiInterface
{
    /**
     * HTTP client for API requests
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Settings manager
     *
     * @var SimpleSettingsManager
     */
    protected $settingsManager;

    /**
     * Gateway configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor
     *
     * @param HttpClient $httpClient
     * @param SimpleSettingsManager $settingsManager
     * @param array $config Gateway configuration
     */
    public function __construct(HttpClient $httpClient, SimpleSettingsManager $settingsManager, array $config = [])
    {
        $this->httpClient = $httpClient;
        $this->settingsManager = $settingsManager;
        $this->config = $config;
    }

    /**
     * Get API base URL based on environment
     *
     * @return string
     */
    protected function getApiBaseUrl(): string
    {
        $urls = $this->settingsManager->getEnvironmentUrls($this->config['environment'] ?? 'sandbox');
        return $urls['api_url'];
    }

    /**
     * Get API query URL based on environment
     *
     * @return string
     */
    protected function getApiQueryUrl(): string
    {
        $urls = $this->settingsManager->getEnvironmentUrls($this->config['environment'] ?? 'sandbox');
        return $urls['api_query_url'];
    }

    /**
     * Get authentication headers
     *
     * @return array
     */
    protected function getAuthHeaders(): array
    {
        if (empty($this->config['merchant_id']) || empty($this->config['merchant_key'])) {
            throw new AuthenticationException('Merchant ID and Key are required');
        }

        return [
            'MerchantId' => $this->config['merchant_id'],
            'MerchantKey' => $this->config['merchant_key']
        ];
    }

    /**
     * Make authenticated API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request data
     * @return array Response data
     * @throws PaymentException
     */
    protected function makeApiRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->getApiBaseUrl() . $endpoint;
        $headers = $this->getAuthHeaders();

        try {
            switch (strtoupper($method)) {
                case 'GET':
                    $response = $this->httpClient->get($url, $headers);
                    break;
                case 'POST':
                    $response = $this->httpClient->post($url, $data, $headers);
                    break;
                case 'PUT':
                    $response = $this->httpClient->put($url, $data, $headers);
                    break;
                default:
                    throw new ValidationException("Unsupported HTTP method: {$method}");
            }

            return $this->handleApiResponse($response);

        } catch (NetworkException $e) {
            throw new PaymentException(
                'API request failed: ' . $e->getMessage(),
                $e->getCode(),
                $e,
                $e->getPaymentErrorCode(),
                $e->getErrorData()
            );
        }
    }

    /**
     * Handle API response and extract relevant data
     *
     * @param array $response HTTP response
     * @return array Processed response data
     * @throws PaymentException
     */
    protected function handleApiResponse(array $response): array
    {
        $data = $response['data'] ?? [];

        // Check for API errors in response
        if (isset($data['ErrorReport'])) {
            $errors = $data['ErrorReport'];
            $errorMessage = 'API Error: ';
            
            if (is_array($errors)) {
                $errorMessage .= implode(', ', array_column($errors, 'Message'));
            } else {
                $errorMessage .= $errors['Message'] ?? 'Unknown error';
            }

            throw new PaymentException(
                $errorMessage,
                $response['status_code'],
                null,
                $errors['Code'] ?? null,
                $data
            );
        }

        return $data;
    }

    /**
     * Generate transaction ID
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        return uniqid('cielo_', true);
    }

    /**
     * Format amount for API (cents)
     *
     * @param float $amount Amount in currency units
     * @return int Amount in cents
     */
    protected function formatAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Métodos abstratos da interface PaymentApiInterface que devem ser implementados pelas classes filhas
     */
    
    /**
     * Processa um pagamento - método abstrato
     */
    abstract public function processPayment(array $payment_data): array;

    /**
     * Cria uma transação PIX - método abstrato
     */
    abstract public function createPixTransaction(array $pix_data): array;

    /**
     * Processa um pagamento com cartão de crédito - método abstrato
     */
    abstract public function processCreditPayment(array $credit_data): array;

    /**
     * Processa um pagamento com cartão de débito - método abstrato
     */
    abstract public function processDebitPayment(array $debit_data): array;

    /**
     * Processa um pagamento com Google Pay - método abstrato
     */
    abstract public function processGooglePayPayment(array $google_pay_data): array;

    /**
     * Consulta o status de uma transação - método abstrato
     */
    abstract public function getTransactionStatus(string $transaction_id): array;

    /**
     * Cancela uma transação - método abstrato
     */
    abstract public function cancelTransaction(string $transaction_id): array;

    /**
     * Captura uma transação pré-autorizada - método abstrato
     */
    abstract public function captureTransaction(string $transaction_id, float $amount): array;

    /**
     * Processa webhook de notificação - método abstrato
     */
    abstract public function processWebhook(array $webhook_data): array;

    /**
     * Obtém as configurações necessárias para o gateway - método abstrato
     */
    abstract public function getRequiredSettings(): array;

    /**
     * Validate payment credentials
     *
     * @return bool True if credentials are valid
     * @throws PaymentException
     */
    public function validateCredentials(): bool
    {
        try {
            // Make a simple API call to validate credentials
            $this->makeApiRequest('GET', 'v2/merchants/' . $this->config['merchant_id']);
            return true;
        } catch (AuthenticationException $e) {
            return false;
        }
    }

    /**
     * Common payment data validation
     *
     * @param array $paymentData Payment data
     * @throws ValidationException
     */
    protected function validatePaymentData(array $paymentData): void
    {
        $required = ['amount', 'order_id'];
        
        foreach ($required as $field) {
            if (empty($paymentData[$field])) {
                throw new ValidationException("Required field missing: {$field}");
            }
        }

        if ($paymentData['amount'] <= 0) {
            throw new ValidationException('Amount must be greater than zero');
        }
    }
}
