<?php
/**
 * Interface para os gateways de pagamento
 *
 * Define os contratos que todos os gateways de pagamento devem implementar.
 * Segue os padrões PSR-4 e está desacoplada do WordPress.
 *
 * @package     Lkn\WcCieloPaymentGateway
 * @subpackage  Contracts
 * @since       1.25.0
 */

namespace Lkn\WcCieloPaymentGateway\Contracts;

/**
 * Interface PaymentApiInterface
 * 
 * Define os métodos que todos os gateways de pagamento devem implementar.
 * Esta interface é parte da camada de lógica de negócio (PSR-4).
 */
interface PaymentApiInterface
{
    /**
     * Processa um pagamento
     *
     * @param array $payment_data Dados do pagamento
     * @return array Resposta do processamento
     */
    public function processPayment(array $payment_data): array;

    /**
     * Cria uma transação PIX
     *
     * @param array $pix_data Dados para a transação PIX
     * @return array Resposta da criação da transação PIX
     */
    public function createPixTransaction(array $pix_data): array;

    /**
     * Processa um pagamento com cartão de crédito
     *
     * @param array $credit_data Dados do cartão de crédito
     * @return array Resposta do processamento do crédito
     */
    public function processCreditPayment(array $credit_data): array;

    /**
     * Processa um pagamento com cartão de débito
     *
     * @param array $debit_data Dados do cartão de débito
     * @return array Resposta do processamento do débito
     */
    public function processDebitPayment(array $debit_data): array;

    /**
     * Processa um pagamento com Google Pay
     *
     * @param array $google_pay_data Dados do Google Pay
     * @return array Resposta do processamento do Google Pay
     */
    public function processGooglePayPayment(array $google_pay_data): array;

    /**
     * Consulta o status de uma transação
     *
     * @param string $transaction_id ID da transação
     * @return array Status da transação
     */
    public function getTransactionStatus(string $transaction_id): array;

    /**
     * Cancela uma transação
     *
     * @param string $transaction_id ID da transação
     * @return array Resposta do cancelamento
     */
    public function cancelTransaction(string $transaction_id): array;

    /**
     * Captura uma transação pré-autorizada
     *
     * @param string $transaction_id ID da transação
     * @param float $amount Valor a ser capturado
     * @return array Resposta da captura
     */
    public function captureTransaction(string $transaction_id, float $amount): array;

    /**
     * Processa webhook de notificação
     *
     * @param array $webhook_data Dados do webhook
     * @return array Resposta do processamento do webhook
     */
    public function processWebhook(array $webhook_data): array;

    /**
     * Valida as credenciais da API
     *
     * @return bool True se as credenciais são válidas
     */
    public function validateCredentials(): bool;

    /**
     * Obtém as configurações necessárias para o gateway
     *
     * @return array Array com as configurações obrigatórias
     */
    public function getRequiredSettings(): array;
}
