<?php
namespace Lkn\WCCieloPaymentGateway\Includes;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use WP_Error;
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

        // Define a URL da API com o BIN do cartão
        $url = ('production' == $debitOption['env']) ? 'https://apiquery.cieloecommerce.cielo.com.br/1/cardBin/' : 'https://apiquerysandbox.cieloecommerce.cielo.com.br/1/cardBin/';
        $url = $url . $cardBin;

        // Configura os cabeçalhos da requisição
        $headers = array(
            'Accept' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantKey
        );

        // Faz a requisição utilizando wp_remote_get
        $response = wp_remote_get($url, array(
            'headers' => $headers
        ));

        // Verifica se houve algum erro na requisição
        if (is_wp_error($response)) {
            return new WP_Error('request_failed', __('Failed to retrieve card type', 'your-text-domain'), array('status' => 500));
        }

        // Obtém o corpo da resposta
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return new WP_REST_Response($data, 200);
    }
}