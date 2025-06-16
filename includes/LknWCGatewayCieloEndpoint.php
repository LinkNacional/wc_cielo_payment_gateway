<?php
namespace Lkn\WCCieloPaymentGateway\Includes;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use WC_Logger;
use WP_Error;
use WP_REST_Response;

final class LknWCGatewayCieloEndpoint {
    public function registerOrderCaptureEndPoint(): void {
        register_rest_route('lknWCGatewayCielo', '/checkCard', array(
            'methods' => 'GET',
            'callback' => array($this, 'orderCapture'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('lknWCGatewayCielo', '/clearOrderLogs', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'clearOrderLogs'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('lknWCGatewayCielo', '/getAcessToken', array(
            'methods' => 'GET',
            'callback' => array($this, 'getAcessToken'),
            'permission_callback' => '__return_true',
        ));
    }

    public function orderCapture($request) {
        // Obtém os parâmetros da requisição
        $parameters = $request->get_params();
        $cardBin = $parameters['cardbin'];

        // Inicializa a classe para obter as credenciais
        $debitOption = get_option('woocommerce_lkn_cielo_debit_settings');
        $merchantId = $debitOption['merchant_id'];
        $merchantKey = $debitOption['merchant_key'];
        $log = new WC_Logger();

        // Define a URL da API com o BIN do cartão
        $url = ('production' == $debitOption['env']) ? 'https://apiquery.cieloecommerce.cielo.com.br/1/cardBin/' : 'https://apiquerysandbox.cieloecommerce.cielo.com.br/1/cardBin/';
        $url = $url . $cardBin;
        $url = apply_filters('lkn_wc_change_bin_url', $url);

        // Configura os cabeçalhos da requisição
        $headers = array(
            'Accept' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantKey
        );
        $headers = apply_filters('lkn_wc_change_bin_headers', $headers);

        // Faz a requisição utilizando wp_remote_get
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 120
        ));

        if ('yes' === $debitOption['debug']) {
            $log->log('info', json_encode($response), array('source' => 'woocommerce-cielo-debit-bin'));
        }

        $response = apply_filters('lkn_wc_check_bin_response', $response, $debitOption, $cardBin);

        // Verifica se houve algum erro na requisição
        if (is_wp_error($response)) {
            return new WP_Error('request_failed', __('Failed to retrieve card type', 'lkn-wc-gateway-cielo'), array('status' => 500));
        }

        // Obtém o corpo da resposta
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['cardQuery'])) {
            return new WP_REST_Response($data['cardQuery'], 200);
        }

        return new WP_REST_Response($data, 200);
    }

    public function clearOrderLogs($request) {
        $args = array(
            'limit' => -1, // Sem limite, pega todas as ordens
            'meta_key' => 'lknWcCieloOrderLogs', // Meta key específica
            'meta_compare' => 'EXISTS', // Verifica se a meta key existe
        );

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            $order->delete_meta_data('lknWcCieloOrderLogs');
            $order->save();
        }

        return new WP_REST_Response($orders, 200);
    }
    
    public function getAcessToken() {
        $LknWCGatewayCieloDebitClass = new LknWCGatewayCieloDebit();
        $acessToken = $LknWCGatewayCieloDebitClass->generate_debit_auth_token();

        return new WP_REST_Response($acessToken, 200);
    }
}