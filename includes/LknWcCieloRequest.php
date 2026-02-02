<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use WC_Logger;

final class LknWcCieloRequest
{
    private $urls = array('https://apisandbox.cieloecommerce.cielo.com.br', 'https://api.cieloecommerce.cielo.com.br/');
    private $queryUrl = array('https://apiquerysandbox.cieloecommerce.cielo.com.br/', 'https://apiquery.cieloecommerce.cielo.com.br/');
    private $log;
    private const WC_STATUS_PENDING = 'pending';

    public function __construct()
    {
        if (class_exists('WC_Logger')) {
            $this->log = new WC_Logger();
        }
    }

    /**
     * @param string $name
     * @param float $amount
     *
     * @return array
     */
    public function pix_request($name, $amount, $billingCpfCpnj, $instance, $order, $merchantOrderId)
    {
        $postUrl = get_option('woocommerce_lkn_wc_cielo_pix_settings')['env'] != 'sandbox' ? $this->urls[1] : $this->urls[0];
        $options = get_option('woocommerce_lkn_wc_cielo_pix_settings');
        // Format the amount to not have decimal separator
        $amount = (int) number_format($amount, 2, '', '');

        $body = array(
            'MerchantOrderId' => $merchantOrderId,
            'Customer' => array(
                'Name' => $name,
                'Identity' => $this->maskSensitiveData($billingCpfCpnj['Identity']),
                'IdentityType' => $billingCpfCpnj['IdentityType'],
            ),
            'Payment' => array(
                'Type' => 'Pix',
                'Amount' => $amount
            )
        );

        $header = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $options['merchant_id'],
            'MerchantKey' => $options['merchant_key']
        );

        $response = wp_remote_post($postUrl . '/1/sales/', array(
            'body' => wp_json_encode($body),
            'headers' => $header,
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            return array(
                'sucess' => false,
                'response' => null
            );
        }

        $response = json_decode($response['body'], true);

        if (isset($response['Payment']['ReturnCode']) && $response['Payment']['ReturnCode'] === '422') {
            return array(
                'sucess' => false,
                'response' => 'Error on merchantResponse integration.'
            );
        }

        if (
            $response == null ||
            (is_array($response) && isset($response[0]) && isset($response[0]['Code']) &&
                ($response[0]['Code'] == '129' || $response[0]['Code'] == '132' || $response[0]['Code'] == '101'))
        ) {
            return array(
                'sucess' => false,
                'response' => 'Invalid credential(s).'
            );
        }
        if ($instance->get_option('debug') === 'yes') {
            $LknWcCieloHelper = new LknWcCieloHelper();

            $orderLogsArray = array(
                'url' => $postUrl . '1/sales',
                'headers' => array(
                    'Content-Type' => $header['Content-Type'],
                    'MerchantId' => $LknWcCieloHelper->censorString($header['MerchantId'], 10),
                    'MerchantKey' => $LknWcCieloHelper->censorString($header['MerchantKey'], 10)
                ),
                'body' => $body,
                'response' => json_decode(json_encode($response), true)
            );

            unset($orderLogsArray['response']['Payment']['Links']);

            $orderLogs = json_encode($orderLogsArray);
            $order->update_meta_data('lknWcCieloOrderLogs', $orderLogs);
        }

        // Mascarar campos sensíveis na resposta antes de fazer o log completo
        $response['Customer']['Identity'] = $this->maskSensitiveData($response['Customer']['Identity']);

        // Da mesma forma, mascarar os campos sensíveis do header
        $header['MerchantId'] = $this->maskSensitiveData($header['MerchantId']);
        $header['MerchantKey'] = $this->maskSensitiveData($header['MerchantKey']);

        // Registrar o log completo com os dados mascarados
        if ('yes' == $instance->get_option('debug')) {
            $this->log->log('info', 'pixRequest', array(
                'request' => array(
                    'url' => $postUrl . '/1/sales/',
                    'current_time' => current_time('mysql'),
                    'body' => $body,
                    'header' => $header,
                ),
                'response' => $response
            ));
        }

        return array(
            'sucess' => true,
            'response' => array(
                'qrcodeImage' => $response['Payment']['QrCodeBase64Image'],
                'qrcodeString' => $response['Payment']['QrCodeString'],
                'status' => $response['Payment']['Status'],
                'paymentId' => $response['Payment']['PaymentId']
            )
        );
    }

    private function maskSensitiveData($string)
    {
        $length = strlen($string);

        if ($length <= 12) {
            return $string;
        } // Retorna sem alterações se o texto for muito curto

        // Calcula quantos caracteres manter no início e no final
        $startLength = intdiv($length - 8, 2);
        $endLength = $length - $startLength - 8;

        $start = substr($string, 0, $startLength);
        $end = substr($string, -$endLength);

        return $start . str_repeat('*', 8) . $end;
    }

    public static function check_payment($paymentId, $order_id): void
    {
        if (empty($paymentId)) {
            $timestamp = wp_next_scheduled('lkn_schedule_check_free_pix_payment_hook', array($paymentId, $order_id));
            if ($timestamp !== false) {
                wp_unschedule_event($timestamp, 'lkn_schedule_check_free_pix_payment_hook', array($paymentId, $order_id));
            }
        } else {
            $instance = new self();
            $order = wc_get_order($order_id);
            $response = $instance->payment_request($paymentId);

            $response = wp_remote_retrieve_body($response);
            if (! wp_next_scheduled('lkn_remove_custom_cron_job_hook', array($paymentId, $order_id))) {
                wp_schedule_single_event(time() + (120 * 60), 'lkn_remove_custom_cron_job_hook', array($paymentId, $order_id));
            }

            if (get_option('woocommerce_lkn_wc_cielo_pix_settings')['debug'] == 'yes') {
                $instance->log->notice($response, array('source' => 'woocommerce-cielo-pix'));
            }
            if ($order->get_status() === self::WC_STATUS_PENDING) {
                $order->update_status($instance->update_status($response));
            }
        }
    }

    public function pixCompleteStatus()
    {
        $pixOptions = get_option('woocommerce_lkn_wc_cielo_pix_settings');
        $status = $pixOptions['payment_complete_status'];

        if ("" == $status) {
            $status = 'processing';
        }

        return $status;
    }

    private function update_status($response)
    {
        $response = json_decode($response, true);
        if (!is_array($response) || !isset($response['Payment'])) {
            return 'cancelled';
        }
        $payment_status = (int) $response['Payment']['Status'];

        switch ($payment_status) {
            case 1:
                return $this->pixCompleteStatus();
                break;
            case 2:
                return $this->pixCompleteStatus();
                break;
            case 12:
                return 'pending';
                break;
            case 3:
                return 'cancelled';
                break;
            case 10:
                return 'cancelled';
                break;
            default:
                return 'cancelled';
                break;
        }
    }

    private function payment_request($paymentId)
    {
        $postUrl = get_option('woocommerce_lkn_wc_cielo_pix_settings')['env'] != 'sandbox' ? $this->queryUrl[1] : $this->queryUrl[0];
        $options = get_option('woocommerce_lkn_wc_cielo_pix_settings');

        $header = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $options['merchant_id'],
            'MerchantKey' => $options['merchant_key']
        );

        $response = wp_remote_get($postUrl . '1/sales/' . $paymentId, array(
            'headers' => $header,
            'timeout' => 120,
        ));

        if (is_wp_error($response)) {
            $this->log->error('Request failed', array('error' => $response->get_error_message()));
            return $response;
        }

        return $response;
    }

    public static function lkn_remove_custom_cron_job($paymentId, $orderId): void
    {
        $timestamp = wp_next_scheduled('lkn_schedule_check_free_pix_payment_hook', array($paymentId, $orderId));
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'lkn_schedule_check_free_pix_payment_hook', array($paymentId, $orderId));
        }
        $timestamp = wp_next_scheduled('lkn_remove_custom_cron_job_hook', array($paymentId, $orderId));
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'lkn_remove_custom_cron_job_hook', array($paymentId, $orderId));
        }
    }
}
