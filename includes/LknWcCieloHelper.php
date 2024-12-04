<?php

namespace Lkn\WCCieloPaymentGateway\Includes;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

final class LknWcCieloHelper {
    public function showOrderLogs() {
        $order = wc_get_order( $_GET['id'] );
        $orderLogs = $order->get_meta('lknWcCieloOrderLogs');

        if($orderLogs){
            //carregar css
            wp_enqueue_style( 'lkn-wc-cielo-order-logs', plugin_dir_url( __FILE__ ) . '../resources/css/frontend/lkn-admin-order-logs.css', array(), LKN_WC_CIELO_VERSION, 'all' );

            $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
                ? wc_get_page_screen_id( 'shop-order' )
                : 'shop_order';
        
            add_meta_box(
                'custom',
                'Custom Meta Box',
                [$this, 'custom_metabox_content'],
                $screen,
                'advanced',
                '' // Ajuste a prioridade aqui
            );
        }
    }
    
    // Metabox content
    public function custom_metabox_content( $object ) {
        // Obter o objeto WC_Order
        $order = is_a( $object, 'WP_Post' ) ? wc_get_order( $object->ID ) : $object;
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
        } else {
            // Caso os logs sejam inválidos ou estejam vazios
            echo '<p>' . esc_html__('No valid logs found.', 'my-plugin-textdomain') . '</p>';
        }
    }
    
    
    
    

    public function censorString($string, $censorLength) {
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