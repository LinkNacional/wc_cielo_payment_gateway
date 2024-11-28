<?php
namespace Lkn\WCCieloPaymentGateway\Includes;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use WP_Error;
use WC_Logger;
use WP_REST_Response;

final class LknWCGatewayCieloEndpoint {
    public function registerOrderCaptureEndPoint(): void {
        register_rest_route('lknWCGatewayCielo', '/checkCard', array(
            'methods' => 'GET',
            'callback' => array($this, 'orderCapture'),
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
            'headers' => $headers
        ));

        if ('yes' === $debitOption['debug']) {
            $log->log('info', json_encode($response), array('source' => 'woocommerce-cielo-debit-bin'));
        }

        $response = apply_filters('lkn_wc_check_bin_response', $response, $debitOption, $cardBin);

        // Verifica se houve algum erro na requisição
        if (is_wp_error($response)) {
            return new WP_Error('request_failed', __('Failed to retrieve card type', 'your-text-domain'), array('status' => 500));
        }

        // Obtém o corpo da resposta
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['cardQuery'])) {
            return new WP_REST_Response($data['cardQuery'], 200);
        }

        return new WP_REST_Response($data, 200);
    }
}