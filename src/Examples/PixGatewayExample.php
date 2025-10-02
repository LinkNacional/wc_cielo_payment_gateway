<?php

namespace Lkn\WcCieloPaymentGateway\Examples;

/**
 * Exemplo de como adaptar a classe PIX existente para usar a nova arquitetura
 * 
 * Este exemplo mostra como modificar o método process_payment da classe PIX
 * para usar os novos serviços mantendo total compatibilidade com WooCommerce
 */
class PixGatewayExample
{
    /**
     * Exemplo de como seria o novo método process_payment
     * Este código substituiria o método existente na classe Lkn_Wc_Cielo_Pix
     */
    public function process_payment_example($order_id)
    {
        $order = wc_get_order($order_id);
        $paymentComplete = true;
        
        try {
            // Validação de dados (mantida igual)
            $firstName = sanitize_text_field($order->get_billing_first_name());
            $lastName = sanitize_text_field($order->get_billing_last_name());
            $fullName = trim($firstName . ' ' . $lastName);
            
            if (empty($fullName)) {
                throw new \Exception('Nome não informado');
            }

            // Validação CPF/CNPJ (mantida igual)
            if (isset($_POST['billing_cpf']) && '' === $_POST['billing_cpf']) {
                $_POST['billing_cpf'] = isset($_POST['billing_cnpj']) ? 
                    sanitize_text_field(wp_unslash($_POST['billing_cnpj'])) : '';
            }
            
            $billingCpfCpnj = [
                'Identity' => isset($_POST['billing_cpf']) ? 
                    sanitize_text_field(wp_unslash($_POST['billing_cpf'])) : '',
                'IdentityType' => isset($_POST['billing_cpf']) && 
                    strlen(sanitize_text_field(wp_unslash($_POST['billing_cpf']))) === 14 ? 'CPF' : 'CNPJ'
            ];

            if ('' === $billingCpfCpnj['Identity'] || !$this->validateCpfCnpj($billingCpfCpnj['Identity'])) {
                throw new \Exception(__('Please enter a valid CPF or CNPJ.', 'lkn-wc-gateway-cielo'));
            }

            // ========== AQUI ESTÁ A DIFERENÇA: USO DA NOVA ARQUITETURA ==========
            
            // Em vez de usar self::$request->pix_request(), usamos o adapter
            $response = \Lkn\WcCieloPaymentGateway\Services\GatewayServiceAdapter::processPixPayment(
                $this, // Gateway instance (para pegar configurações)
                $order, // WooCommerce order
                $billingCpfCpnj // Dados de CPF/CNPJ
            );

            // ====================================================================

            // Resto da lógica mantida igual
            if (isset($response['sucess']) && $response['sucess'] === false) {
                throw new \Exception(json_encode($response['response']), 1);
            }
            
            if (!is_array($response) && !is_object($response)) {
                throw new \Exception(json_encode($response), 1);
            }
            
            if (!$response['response']) {
                throw new \Exception('Erro na Requisição. Tente novamente!', 1);
            }

            // Agendar verificação de pagamento (mantido igual)
            if (!wp_next_scheduled('lkn_schedule_check_payment_hook', 
                [$response["response"]["paymentId"], $order_id])) {
                wp_schedule_event(time(), "every_minute", 'lkn_schedule_check_payment_hook', 
                    [$response["response"]["paymentId"], $order_id]);
            }

            // Salvar dados PIX (mantido igual)
            $order->update_meta_data('_wc_cielo_qrcode_image', $response['response']['qrcodeImage']);
            $order->update_meta_data('_wc_cielo_qrcode_string', $response['response']['qrcodeString']);
            $order->update_meta_data('_wc_cielo_qrcode_payment_id', $response['response']['paymentId']);
            $order->save();

        } catch (\Exception $err) {
            $paymentComplete = false;
            $this->add_error($err->getMessage());
        }

        // Resposta final (mantida igual)
        if ($paymentComplete) {
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        } else {
            $this->log->log('error', 'PIX Payment failed: ' . var_export($response, true), 
                ['source' => 'woocommerce-cielo-pix']);
            $this->lkn_add_notice_once(__('PIX Payment Failed', 'lkn-wc-gateway-cielo-pro'), 'error');
            throw new \Exception(esc_attr(__('PIX Payment Failed', 'lkn-wc-gateway-cielo-pro')));
        }
    }

    /**
     * Exemplo de método para verificar status de pagamento usando nova arquitetura
     */
    public function check_payment_status_example($payment_id)
    {
        try {
            $statusResponse = \Lkn\WcCieloPaymentGateway\Services\GatewayServiceAdapter::checkPaymentStatus(
                $this,
                $payment_id
            );
            
            return $statusResponse;
            
        } catch (\Exception $e) {
            error_log('Erro ao verificar status do pagamento: ' . $e->getMessage());
            return null;
        }
    }

    // Métodos de validação mantidos iguais...
    private function validateCpfCnpj($cpfCnpj)
    {
        // Implementação original mantida
        return true; // Simplified for example
    }
}
