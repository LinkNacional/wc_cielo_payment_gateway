<?php

namespace Lkn\WcCieloPaymentGateway\Includes;

use Lkn\WcCieloPaymentGateway\Includes\LknWcGatewayCieloDebit;
use WC_Logger;
use WP_Error;
use WP_REST_Response;

final class LknWcGatewayCieloEndpoint
{
    public function registerOrderCaptureEndPoint(): void
    {
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

        register_rest_route('lknWCGatewayCielo', '/getCardBrand', array(
            'methods' => 'GET',
            'callback' => array($this, 'getOfflineBinCard'),
            'permission_callback' => '__return_true',
            'args' => array(
                'number' => array(
                    'required' => true,
                    'validate_callback' => function ($param) {
                        return strlen($param) >= 6;
                    },
                ),
            ),
        ));
    }

    public function orderCapture($request)
    {
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

    public function clearOrderLogs($request)
    {
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

    public function getAcessToken()
    {
        $LknWcGatewayCieloDebitClass = new LknWcGatewayCieloDebit();
        $acessToken = $LknWcGatewayCieloDebitClass->generate_debit_auth_token();

        return new WP_REST_Response($acessToken, 200);
    }

    /**
     * Retrieves the card brand based on the BIN number.
     *
     * @param WP_REST_Request $request The request object containing the 'number' parameter.
     *
     * @return WP_REST_Response Returns a response with the card brand if recognized, or an error message if not.
     */
    public function getOfflineBinCard($request)
    {
        $number = str_replace(' ', '', trim($request->get_param('number')));

        $bin = [
            // visa
            '/^4[0-9]{2,15}$/',
            // elo
            '/^(431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
            // hipercard
            '/^(606282|3841)\d{0,13}$/',
            // diners
            '/^3(?:0[0-5]|[68][0-9])[0-9]{0,11}$/',
            // discover
            '/^6(?:011|5[0-9]{2})[0-9]{0,12}$/',
            // jcb
            '/^(?:2131|1800|35\d{2})\d{0,11}$/',
            // aura
            '/^50[0-9]{2,17}$/',
            // amex
            '/^3[47][0-9]{2,13}$/',
            // mastercard
            '/^5[1-5]\d{0,14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{0,12}$/',
        ];

        // Test the cardNumber bin
        foreach ($bin as $index => $regex) {
            if (preg_match($regex, $number)) {
                $brands = [
                    'visa',
                    'elo',
                    'hipercard',
                    'diners',
                    'discover',
                    'jcb',
                    'aura',
                    'amex',
                    'mastercard',
                ];

                return new WP_REST_Response([
                    'status' => true,
                    'brand' => $brands[$index],
                ], 200);
            }
        }

        // Caso não encontre nenhuma correspondência
        return new WP_REST_Response([
            'status' => false,
            'message' => __('Card brand not found', 'lkn-wc-gateway-cielo'),
        ], 200);
    }
}
