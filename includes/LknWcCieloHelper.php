<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use WC_Order;

final class LknWcCieloHelper
{
    public function showOrderLogs(): void
    {
        $id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        if (empty($id)) {
            $id = isset($_GET['post']) ? sanitize_text_field(wp_unslash($_GET['post'])) : '';
        }
        if (! empty($id)) {
            $order_id = $id;
            $order = wc_get_order($order_id);

            if ($order && $order instanceof WC_Order) {
                $orderLogs = $order->get_meta('lknWcCieloOrderLogs');
                $payment_method_id = $order->get_payment_method();
                $options = get_option('woocommerce_' . $payment_method_id . '_settings');
                if ($orderLogs && 'yes' === $options['show_order_logs']) {
                    //carregar css
                    wp_enqueue_style('lkn-wc-cielo-order-logs', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-admin-order-logs.css', array(), LKN_WC_CIELO_VERSION, 'all');

                    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')->custom_orders_table_usage_is_enabled()
                        ? wc_get_page_screen_id('shop-order')
                        : 'shop_order';

                    add_meta_box(
                        'showOrderLogs',
                        'Logs das transações',
                        array($this, 'showLogsContent'),
                        $screen,
                        'advanced',
                    );
                }
            }
        }
    }

    // Metabox content
    public function showLogsContent($object): void
    {
        // Obter o objeto WC_Order
        $order = is_a($object, 'WP_Post') ? wc_get_order($object->ID) : $object;
        $orderLogs = $order->get_meta('lknWcCieloOrderLogs');

        // Decodificar o JSON armazenado
        $decodedLogs = json_decode($orderLogs, true);

        if ($decodedLogs && is_array($decodedLogs)) {
            // Preparar cada seção para exibição com formatação
            $url = $decodedLogs['url'] ?? 'N/A';
            $headers = isset($decodedLogs['headers']) ? json_encode($decodedLogs['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A';
            $body = isset($decodedLogs['body']) ? json_encode($decodedLogs['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A';
            $response = isset($decodedLogs['response']) ? json_encode($decodedLogs['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'N/A';

            // Exibir as seções formatadas
            ?>
<div id="lknWcCieloOrderLogs">
    <div>
        <h3>URL:</h3>
        <pre class="wc-pre"><?php echo esc_html($url); ?></pre>
    </div>

    <h3>Headers:</h3>
    <pre class="wc-pre"><?php echo esc_html($headers); ?></pre>

    <h3>Body:</h3>
    <pre class="wc-pre"><?php echo esc_html($body); ?></pre>

    <h3>Response:</h3>
    <pre class="wc-pre"><?php echo esc_html($response); ?></pre>
</div>
<?php
        }
    }

    public function censorString($string, $censorLength)
    {
        $length = strlen($string);

        if ($censorLength >= $length) {
            // Se o número de caracteres a censurar for maior ou igual ao comprimento total, censura tudo
            return str_repeat('*', $length);
        }

        $startLength = floor(($length - $censorLength) / 2); // Dividir o restante igualmente entre início e fim
        $endLength = $length - $startLength - $censorLength; // O que sobra para o final

        $start = substr($string, 0, $startLength);
        $end = substr($string, -$endLength);

        $censored = str_repeat('*', $censorLength);
        return $start . $censored . $end;
    }
}
