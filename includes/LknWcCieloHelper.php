<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use WC_Order;
use HelgeSverre\Toon\Toon;

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
        // Para compatibilidade com código existente, manter esta função
        // Mas recomendar uso da maskCredential para credenciais
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

    /**
     * Verifica se o plugin pro está ativo e se a licença está ativa
     * 
     * @return bool True se o plugin pro e a licença estiverem ativos, false caso contrário
     */
    public static function is_pro_license_active()
    {
        // Verifica se o plugin pro está ativo
        if (!is_plugin_active('lkn-wc-gateway-cielo-pro/lkn-wc-gateway-cielo-pro.php')) {
            return false;
        }

        // Verifica o status da licença
        $license_result = base64_decode(get_option('lknCieloProApiLicense', 'empty'), true);
        
        $license_result = ('active' === $license_result) ? true : false;

        return $license_result;
    }

    public static function getIconUrl()
    {
        return plugin_dir_url(__FILE__) . '../includes/assets/icon.svg';
    }

    /**
     * Encode data using TOON format
     *
     * @param array $data
     * @return string|false
     */
    private static function encodeToonData($data)
    {
        try {
            return Toon::encode($data);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Decode TOON data
     *
     * @param string $toonString
     * @return array|false
     */
    public static function decodeToonData($toonString)
    {
        try {
            return Toon::decode($toonString);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mask credentials dynamically based on string length.
     *
     * @param string $credential
     * @return string
     */
    public static function maskCredential($credential)
    {
        if (empty($credential)) {
            return 'N/A';
        }
        
        $length = strlen($credential);
        
        // Para strings muito pequenas, mascarar tudo
        if ($length <= 6) {
            return str_repeat('*', $length);
        }
        
        // Para strings de 7-8 caracteres, usar 3+asteriscos+3
        if ($length <= 8) {
            $showChars = 3;
        } 
        // Para strings de 9-12 caracteres, usar 4+asteriscos+4
        elseif ($length <= 12) {
            $showChars = 4;
        }
        // Para strings maiores que 12, usar mais caracteres visíveis
        else {
            $showChars = min(6, floor($length / 3)); // Máximo 6 caracteres de cada lado
        }
        
        $start = substr($credential, 0, $showChars);
        $end = substr($credential, -$showChars);
        $middleLength = $length - (2 * $showChars);
        $middle = str_repeat('*', $middleLength);
        
        return $start . $middle . $end;
    }

    /**
     * Get HTTP status description.
     *
     * @param int $httpStatus
     * @return string
     */
    public static function getHttpStatusDescription($httpStatus)
    {
        $httpStatusDescriptions = array(
            200 => 'Sucesso',
            201 => 'Criado com sucesso',
            400 => 'Requisição inválida',
            401 => 'Não autorizado',
            403 => 'Proibido',
            404 => 'Não encontrado',
            405 => 'Método não permitido',
            422 => 'Entidade não processável',
            429 => 'Muitas requisições',
            500 => 'Erro interno do servidor',
            502 => 'Gateway inválido',
            503 => 'Serviço indisponível',
            504 => 'Timeout do gateway'
        );

        return isset($httpStatusDescriptions[$httpStatus]) ? $httpStatusDescriptions[$httpStatus] : 'Status HTTP desconhecido';
    }

    /**
     * Get status description from Cielo response code.
     *
     * @param int $status
     * @return string
     */
    public static function getStatusDescription($status)
    {
        $statusDescriptions = array(
            0 => 'Pendente',
            1 => 'Autorizada',
            2 => 'Autorizada',
            3 => 'Negada',
            10 => 'Cancelada',
            11 => 'Cancelada (Refund)',
            12 => 'Pendente',
            13 => 'Abortada',
            20 => 'Aguardando',
        );

        return isset($statusDescriptions[$status]) ? $statusDescriptions[$status] : 'Desconhecido';
    }

    /**
     * Save transaction metadata to order.
     *
     * @param WC_Order $order
     * @param object $responseDecoded
     * @param string $cardNum
     * @param string $cardExpShort
     * @param string $cardName
     * @param int $installments
     * @param float $amount
     * @param string $currency
     * @param string $provider
     * @param string $merchantId
     * @param string $merchantSecret
     * @param string $merchantOrderId
     * @param int $order_id
     * @param bool $capture
     * @param array $response
     * @param string $cardType
     * @param string $cvvField
     * @param string $gatewayInstance
     * @param string $xid
     * @param string $cavv
     * @param string $eci
     * @param string $version
     * @param string $refId
     */
    public static function saveTransactionMetadata($order, $responseDecoded, $cardNum, $cardExpShort, $cardName, $installments, $amount, $currency, $provider, $merchantId, $merchantSecret, $merchantOrderId, $order_id, $capture, $response, $cardType = 'Credit', $cvvField = 'lkn_cc_cvc', $gatewayInstance = null, $xid = '', $cavv = '', $eci = '', $version = '', $refId = '')
    {
        // Extrair CVV dos dados
        $cardCvv = isset($_POST[$cvvField]) ? sanitize_text_field(wp_unslash($_POST[$cvvField])) : '';
        
        // Calcular valor das parcelas
        $installmentAmount = $installments > 1 ? ($amount / $installments) : $amount;
        
        // Calcular juros/desconto baseado nas parcelas
        $interestDiscountAmount = 0;
        $totalWithFees = $amount; // Valor total já pode incluir juros
        $originalAmount = $order->get_subtotal() + $order->get_shipping_total();
        $difference = $totalWithFees - $originalAmount;
        if ($difference != 0) {
            $interestDiscountAmount = abs($difference);
        }
        
        // Verificar se é pagamento recorrente
        $isRecurrent = 'Não';
        if ($cardType === 'Credit' && class_exists('WC_Subscriptions_Order') && function_exists('WC_Subscriptions_Order::order_contains_subscription')) {
            if (WC_Subscriptions_Order::order_contains_subscription($order_id)) {
                $isRecurrent = 'Sim';
            }
        }
        
        // Data da requisição
        $requestDateTime = current_time('Y-m-d H:i:s');
        
        // Formatar cartão: 4 primeiros + 6 asteriscos + 4 últimos
        $cardMasked = !empty($cardNum) && strlen($cardNum) >= 8 ? 
            substr($cardNum, 0, 4) . ' **** **** ' . substr($cardNum, -4) : 'N/A';
        
        // Return code com descrição da resposta
        $returnCode = isset($responseDecoded->Payment->ReturnCode) ? $responseDecoded->Payment->ReturnCode : '';
        $returnMessage = isset($responseDecoded->Payment->ReturnMessage) ? $responseDecoded->Payment->ReturnMessage : '';
        $returnCodeFormatted = $returnCode ? $returnCode . ' - ' . $returnMessage : 'N/A';
        
        // Gateway via ID do método de pagamento (mais confiável que o título)
        $gatewayName = $order->get_payment_method();
        $gatewayName = !empty($gatewayName) ? $gatewayName : 'N/A';
        
        // Status HTTP da requisição
        $httpStatus = wp_remote_retrieve_response_code($response);
        $httpStatusDescription = self::getHttpStatusDescription($httpStatus);
        $httpStatusFormatted = $httpStatus ? $httpStatus . ' - ' . $httpStatusDescription : 'N/A';
        
        // Formatar dados 3DS
        $threeDSData = '';
        if (!empty($cavv) && !empty($eci) && !empty($xid)) {
            $threeDSData = 'CAVV: ' . substr($cavv, 0, 8) . '..., ECI: ' . $eci . ', Version: ' . $version;
        } else {
            $threeDSData = 'N/A';
        }
        
        // Environment baseado no gateway
        $environment = 'Sandbox';
        if ($gatewayInstance && method_exists($gatewayInstance, 'get_option')) {
            $environment = ($gatewayInstance->get_option('env') == 'production') ? 'Produção' : 'Sandbox';
        }
        
        // Validar e mascarar merchant credentials dinamicamente
        $merchantIdMasked = self::maskCredential($merchantId);
        $merchantKeyMasked = self::maskCredential($merchantSecret);
        
        // Validar data de expiração
        $cardExpiryFormatted = !empty($cardExpShort) ? $cardExpShort : 'N/A';
        
        // Validar CVV baseado no tipo de pagamento
        $cvvSent = 'N/A';
        if (in_array($cardType, ['Credit', 'Debit'])) {
            $cvvSent = !empty($cardCvv) ? 'Sim' : 'Não';
        }
        
        // Validar Capture baseado no tipo de pagamento
        $captureFormatted = 'N/A';
        if (in_array($cardType, ['Credit', 'Debit'])) {
            $captureFormatted = $capture ? 'Sim' : 'Não';
        }
        
        // Validar Recorrente baseado no tipo de pagamento
        $recurrentFormatted = 'N/A';
        if ($cardType === 'Credit') {
            $recurrentFormatted = $isRecurrent;
        }
        
        // Validar 3DS baseado no gateway - só existe para débito/crédito com 3DS
        $threeDSFormatted = 'N/A';
        if ($cardType === 'Debit' && (!empty($cavv) || !empty($eci) || !empty($xid))) {
            if (!empty($cavv) && !empty($eci) && !empty($xid)) {
                $threeDSFormatted = 'Sucesso';
            } else {
                $threeDSFormatted = 'Falhou';
            }
        }
        
        // Formatar valor das parcelas com quantidade - validação N/A
        $installmentFormatted = 'N/A';
        if ($installments > 0 && $installmentAmount > 0) {
            $installmentFormatted = $installments . 'x de ' . wc_price($installmentAmount, array('currency' => $currency));
        }
        
        // Criar estrutura centralizada com metadados da transação
        $transactionMetadata = [
            'card' => [
                'masked' => $cardMasked,
                'type' => $cardType === 'Debit' ? 'Débito' : 'Crédito',
                'brand' => !empty($provider) ? $provider : 'N/A',
                'expiry' => $cardExpiryFormatted,
                'holder_name' => !empty($cardName) ? $cardName : 'N/A'
            ],
            'transaction' => [
                'cvv_sent' => $cvvSent,
                'installments' => $installments > 0 ? $installments : 'N/A',
                'installment_amount' => $installmentFormatted,
                'capture' => $captureFormatted,
                'recurrent' => $recurrentFormatted,
                '3ds_auth' => $threeDSFormatted,
                'tid' => isset($responseDecoded->Payment->Tid) && !empty($responseDecoded->Payment->Tid) 
                    ? $responseDecoded->Payment->Tid : 'N/A'
            ],
            'amounts' => [
                'total' => wc_price($amount, ['currency' => $currency]),
                'subtotal' => wc_price($order->get_subtotal(), ['currency' => $currency]),
                'shipping' => wc_price($order->get_shipping_total(), ['currency' => $currency]),
                'interest_discount' => wc_price($interestDiscountAmount, ['currency' => $currency]),
                'currency' => $currency
            ],
            'system' => [
                'request_datetime' => $requestDateTime,
                'environment' => $environment,
                'gateway' => $gatewayName,
                'order_id' => $order_id,
                'reference' => !empty($merchantOrderId) ? $merchantOrderId : 'N/A'
            ],
            'merchant' => [
                'id_masked' => $merchantIdMasked,
                'key_masked' => $merchantKeyMasked
            ],
            'response' => [
                'return_code' => $returnCodeFormatted,
                'http_status' => $httpStatusFormatted
            ]
        ];

        // Tentar codificar com TOON
        $toonEncoded = self::encodeToonData($transactionMetadata);

        if ($toonEncoded !== false) {
            // Salvar dados como TOON
            $order->add_meta_data('lkn_cielo_transaction_data', $toonEncoded, true);
            $order->add_meta_data('lkn_cielo_data_format', 'toon', true);
        } else {
            // Fallback para JSON se TOON falhar
            $jsonEncoded = wp_json_encode($transactionMetadata);
            $order->add_meta_data('lkn_cielo_transaction_data', $jsonEncoded, true);
            $order->add_meta_data('lkn_cielo_data_format', 'json', true);
        }

        // Manter campos críticos para compatibilidade backward  
        $paymentId = isset($responseDecoded->Payment->PaymentId) && !empty($responseDecoded->Payment->PaymentId) 
            ? $responseDecoded->Payment->PaymentId : '';
        $nsu = isset($responseDecoded->Payment->ProofOfSale) && !empty($responseDecoded->Payment->ProofOfSale) 
            ? $responseDecoded->Payment->ProofOfSale : '';

        if (!empty($paymentId)) {
            $order->add_meta_data('paymentId', $paymentId, true);
        }
        if (!empty($nsu)) {
            $order->update_meta_data('lkn_nsu', $nsu);
        }
        
        // Log para verificar estrutura TOON
        error_log('=== CIELO TOON METADATA TEST ===');
        error_log('Order ID: ' . $order_id);
        error_log('Format: ' . ($toonEncoded !== false ? 'TOON' : 'JSON'));
        error_log('Data Structure: ' . print_r($transactionMetadata, true));
        error_log('=== END CIELO TOON METADATA TEST ===');
    }

    /**
     * Get structured transaction metadata from order
     *
     * @param WC_Order $order
     * @return array|null
     */
    public static function getTransactionMetadata($order)
    {
        $encodedData = $order->get_meta('lkn_cielo_transaction_data');
        $format = $order->get_meta('lkn_cielo_data_format');
        
        if (empty($encodedData)) {
            return null;
        }
        
        // Tentar decodificar baseado no formato
        if ($format === 'toon') {
            $decodedData = self::decodeToonData($encodedData);
            if ($decodedData !== false) {
                return $decodedData;
            }
        }
        
        // Fallback para JSON
        $jsonDecoded = json_decode($encodedData, true);
        return is_array($jsonDecoded) ? $jsonDecoded : null;
    }

    /**
     * Get flattened transaction data for admin display
     *
     * @param WC_Order $order
     * @return array
     */
    public static function getFormattedTransactionData($order)
    {
        $data = self::getTransactionMetadata($order);
        
        if (!$data) {
            return [];
        }
        
        $flatData = [];
        
        // Dados do cartão
        if (isset($data['card'])) {
            $flatData['Cartão Mascarado'] = $data['card']['masked'];
            $flatData['Tipo do Cartão'] = $data['card']['type'];
            $flatData['Bandeira'] = $data['card']['brand'];
            $flatData['Vencimento'] = $data['card']['expiry'];
            $flatData['Nome do Portador'] = $data['card']['holder_name'];
        }
        
        // Dados da transação
        if (isset($data['transaction'])) {
            $flatData['CVV Enviado'] = $data['transaction']['cvv_sent'];
            $flatData['Parcelas'] = $data['transaction']['installments'];
            $flatData['Valor da Parcela'] = $data['transaction']['installment_amount'];
            $flatData['Captura'] = $data['transaction']['capture'];
            $flatData['Recorrente'] = $data['transaction']['recurrent'];
            $flatData['3DS Auth'] = $data['transaction']['3ds_auth'];
            $flatData['TID'] = $data['transaction']['tid'];
        }
        
        // Valores
        if (isset($data['amounts'])) {
            $flatData['Valor Total'] = $data['amounts']['total'];
            $flatData['Subtotal'] = $data['amounts']['subtotal'];
            $flatData['Frete'] = $data['amounts']['shipping'];
            $flatData['Juros/Desconto'] = $data['amounts']['interest_discount'];
            $flatData['Moeda'] = $data['amounts']['currency'];
        }
        
        // Sistema
        if (isset($data['system'])) {
            $flatData['Data/Hora'] = $data['system']['request_datetime'];
            $flatData['Ambiente'] = $data['system']['environment'];
            $flatData['Gateway'] = $data['system']['gateway'];
            $flatData['ID do Pedido'] = $data['system']['order_id'];
            $flatData['Referência'] = $data['system']['reference'];
        }
        
        // Merchant
        if (isset($data['merchant'])) {
            $flatData['Merchant ID'] = $data['merchant']['id_masked'];
            $flatData['Merchant Key'] = $data['merchant']['key_masked'];
        }
        
        // Resposta
        if (isset($data['response'])) {
            $flatData['Código de Retorno'] = $data['response']['return_code'];
            $flatData['Status HTTP'] = $data['response']['http_status'];
        }
        
        return $flatData;
    }

    /**
     * Get transaction data in JSON format for JavaScript
     *
     * @param WC_Order $order
     * @return string
     */
    public static function getTransactionDataForJS($order)
    {
        $data = self::getTransactionMetadata($order);
        $format = $order->get_meta('lkn_cielo_data_format');
        
        if (!$data) {
            return wp_json_encode(['error' => 'No transaction data found']);
        }
        
        return wp_json_encode([
            'data' => $data,
            'format' => $format ?: 'json',
            'flattened' => self::getFormattedTransactionData($order)
        ]);
    }

    /**
     * Get card provider from number.
     *
     * @param string $cardNumber
     * @param string $gatewayId
     *
     * @return string|bool
     */
    public static function getCardProvider($cardNumber, $gatewayId = '')
    {
        $brand = '';
        $brand = apply_filters('lkn_wc_cielo_get_card_brand', $brand, $cardNumber, $gatewayId);

        if (empty($brand)) {
            // Stores regex for Card Bin Tests
            $bin = array(
                // elo
                '/(4011|431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
                // hipercard
                '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
                // diners
                '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
                // discover
                '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
                // jcb
                '/^(?:2131|1800|35\d{3})\d{11}$/',
                // aura
                '/^50[0-9]{14,17}$/',
                // amex
                '/^3[47][0-9]{13}$/',
                // mastercard
                '/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/',
                // visa
                '/^4[0-9]{12}(?:[0-9]{3})?$/',
            );

            // Test the cardNumber bin
            for ($c = 0; $c < count($bin); ++$c) {
                if ($c > 10) {
                    break;
                }
                if (preg_match($bin[$c], $cardNumber) == 1) {
                    switch ($c) {
                        case 0:
                            return 'Elo';

                            break;

                        case 1:
                            return 'Hipercard';

                            break;

                        case 2:
                            return 'Diners';

                            break;

                        case 3:
                            return 'Discover';

                            break;

                        case 4:
                            return 'JCB';

                            break;

                        case 5:
                            return 'Aura';

                            break;

                        case 6:
                            return 'Amex';

                            break;

                        case 7:
                            return 'Master';

                            break;

                        case 8:
                            return 'Visa';

                            break;
                    }
                }
            }
        } else {
            return sanitize_text_field($brand);
        }

        return false;
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    public static function getClientIp()
    {
        $ip_address = '';
        $client_ip = isset($_SERVER['HTTP_CLIENT_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP'])) : '';
        $forwarded_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])) : '';
        $real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP'])) : '';
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (! empty($client_ip)) {
            $ip_address = $client_ip;
        } elseif (! empty($forwarded_ip)) {
            // Se estiver atrás de um proxy, `HTTP_X_FORWARDED_FOR` pode conter uma lista de IPs.
            $ip_list = explode(',', $forwarded_ip);
            $ip_address = trim($ip_list[0]); // Pega o primeiro IP da lista
        } elseif (! empty($real_ip)) {
            $ip_address = $real_ip;
        } else {
            $ip_address = $remote_ip;
        }

        return $ip_address;
    }
}
