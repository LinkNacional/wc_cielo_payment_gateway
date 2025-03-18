<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Exception;
use WC_Logger;
use WC_Payment_Gateway;

final class LknWcCieloPix extends WC_Payment_Gateway
{
    /**
     * @var string
     */
    private $version;

    /**
     * Request
     *
     * @var LknWcCieloRequest
     */
    private static $request;
    private $log;
    private $instructions;

    public function __construct()
    {
        $this->id = 'lkn_wc_cielo_pix';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        self::$request = new LknWcCieloRequest();
        $this->version = LKN_WC_CIELO_VERSION;

        $this->method_title = __('Cielo Free - Pix', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows pix payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo') . ' ' .
        __('Before using Pix in production, make sure that Pix is enabled in your registration. To confirm, just access the', 'lkn-wc-gateway-cielo') . ' ' .
        '<a href="https://www.cielo.com.br/" target="_blank">' .
        __('Cielo portal', 'lkn-wc-gateway-cielo') . '</a>' . ' ' . __('in the logged-in area under My Registration > Authorizations > PIX', 'lkn-wc-gateway-cielo');

        $this->supports = array(
            'products',
        );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        //Actions

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Define user set variables.

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->debug = $this->get_option('debug');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->log = new WC_Logger();

        if (function_exists('get_plugins')) {
            add_action('admin_enqueue_scripts', array($this, 'admin_load_script'));
        }
    }

    public function admin_load_script(): void
    {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        if ('wc-settings' === $page && 'checkout' === $tab && $section == $this->id) {
            wp_enqueue_script('LknCieloPixSettingsLayoutScript', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/admin/lkn-wc-gateway-admin-layout.js', array('jquery'), $this->version, false);
            wp_enqueue_style('lkn-admin-cielo-layout', LKN_WC_GATEWAY_CIELO_URL . 'resources/css/frontend/lkn-admin-layout.css', array(), $this->version, 'all');
            wp_enqueue_script('LknCieloPixClearButtonScript', LKN_WC_GATEWAY_CIELO_URL . '/resources/js/admin/lkn-clear-logs-button.js', array('jquery'), $this->version, false);
            wp_localize_script('LknCieloPixClearButtonScript', 'lknWcCieloTranslations', array(
                'clearLogs' => __('Limpar Logs', 'lkn-wc-gateway-cielo'),
                'alertText' => __('Deseja realmente deletar todos logs dos pedidos?', 'lkn-wc-gateway-cielo')
            ));

            if (isset($_GET['section']) && sanitize_text_field(wp_unslash($_GET['section'])) === 'lkn_wc_cielo_pix') {
                wp_enqueue_script('LknCieloPixSettingsPix', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/admin/lkn-settings-pix.js', array(), $this->version, false);
            }
        }
    }

    public function init_form_fields(): void
    {
        $this->form_fields = array(
            'general' => array(
                'title' => esc_attr__('General', 'lkn-wc-gateway-cielo'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Pix Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Pix', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'description' => array(
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'default' => __('After the purchase is completed, the PIX will be generated and made available for payment!', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'env' => array(
                'title' => __('Environment', 'lkn-wc-gateway-cielo'),
                'description' => __('Cielo API 3.0 environment.', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'options' => array(
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                    'sandbox' => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default' => 'production',
                'desc_tip' => true,
            ),
            'merchant_id' => array(
                'title' => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'merchant_key' => array(
                'title' => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'payment_complete_status' => array(
                'title' => esc_attr__('Payment Complete Status', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'desc_tip' => true,
                'description' => esc_attr__('Option to automatically set the order status after payment confirmation through this gateway.', 'lkn-wc-gateway-cielo'),
                'options' => array(
                    'processing' => _x('Processing', 'Order status', 'woocommerce'),
                    'on-hold' => _x('On hold', 'Order status', 'woocommerce'),
                    'completed' => _x('Completed', 'Order status', 'woocommerce'),
                ),
                'default' => 'processing'
            ),
            'pix_layout' => array(
                'title' => __('PIX Layout', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'default' => 'standard',
                'description' => __('Select the PIX layout the customer will see on checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'options' => array(
                    'standard' => __('Standard', 'lkn-wc-gateway-cielo'),
                    'new' => __('New', 'lkn-wc-gateway-cielo')
                )
            ),
            'layout_location' => array(
                'title' => __('Layout Location', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'default' => 'bottom',
                'description' => __('Select the location where the PIX layout will be displayed on the checkout page.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'options' => array(
                    'top' => __('Top', 'lkn-wc-gateway-cielo'),
                    'bottom' => __('Bottom', 'lkn-wc-gateway-cielo')
                )
            ),
            'developer' => array(
                'title' => esc_attr__('Developer', 'lkn-wc-gateway-cielo'),
                'type' => 'title',
            ),
            'debug' => array(
                'title' => __('Debug', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => sprintf(
                    '%1$s. <a href="%2$s">%3$s</a>',
                    __('Enable log capture for payments', 'lkn-wc-gateway-cielo'),
                    admin_url('admin.php?page=wc-status&tab=logs'),
                    __('View logs', 'lkn-wc-gateway-cielo')
                ),
                'default' => 'no',
            ),
        );

        if ($this->get_option('debug') == 'yes') {
            $this->form_fields['show_order_logs'] =  array(
                'title' => __('Visualizar Log no Pedido', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => sprintf('Habilita visualização do log da transação dentro do pedido.', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            );
            $this->form_fields['clear_order_records'] =  array(
                'title' => __('Limpar logs nos Pedidos', 'lkn-wc-gateway-cielo'),
                'type' => 'button',
                'id' => 'validateLicense',
                'class' => 'woocommerce-save-button components-button is-primary'
            );
        }

        $customConfigs = apply_filters('lkn_wc_cielo_get_custom_configs', array(), $this->id);

        if (! empty($customConfigs)) {
            $this->form_fields = array_merge($this->form_fields, $customConfigs);
        }
    }

    public function payment_fields(): void
    {
        $description = $this->get_option('description', __('After the purchase is completed, the PIX will be generated and made available for payment!', 'lkn-wc-gateway-cielo'));
        echo "
            <div style=\"text-align:center;font-weight: bold;\">
                <p>" . esc_attr($description) . "</p>
            </div>
        ";

        $wcbcf_settings = get_option('wcbcf_settings');
        if (
            is_array($wcbcf_settings) &&
            "0" === $wcbcf_settings['person_type'] ||
            ! is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')
        ) {
            ?>
<br />
<div class="form-row form-row">
    <label
        id="labels-with-icons"
        for="lknCieloApiProPixBillingCpf"
        style="display: flex; align-items: center;"
    >
        <?php echo esc_attr('CPF / CNPJ'); ?><span
            class="required"
        >*</span>
        <div>
            <svg
                version="1.1"
                id="Capa_1"
                xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                x="0px"
                y="4px"
                width="24px"
                height="16px"
                viewBox="0 0 216 146"
                enable-background="new 0 0 216 146"
                xml:space="preserve"
            >
                <g>
                    <path
                        class="svg"
                        d="M107.999,73c8.638,0,16.011-3.056,22.12-9.166c6.111-6.11,9.166-13.483,9.166-22.12c0-8.636-3.055-16.009-9.166-22.12c-6.11-6.11-13.484-9.165-22.12-9.165c-8.636,0-16.01,3.055-22.12,9.165c-6.111,6.111-9.166,13.484-9.166,22.12c0,8.637,3.055,16.01,9.166,22.12C91.99,69.944,99.363,73,107.999,73z"
                        style="fill: rgb(21, 140, 186);"
                    ></path>
                    <path
                        class="svg"
                        d="M165.07,106.037c-0.191-2.743-0.571-5.703-1.141-8.881c-0.57-3.178-1.291-6.124-2.16-8.84c-0.869-2.715-2.037-5.363-3.504-7.943c-1.466-2.58-3.15-4.78-5.052-6.6s-4.223-3.272-6.965-4.358c-2.744-1.086-5.772-1.63-9.085-1.63c-0.489,0-1.63,0.584-3.422,1.752s-3.815,2.472-6.069,3.911c-2.254,1.438-5.188,2.743-8.799,3.909c-3.612,1.168-7.237,1.752-10.877,1.752c-3.639,0-7.264-0.584-10.876-1.752c-3.611-1.166-6.545-2.471-8.799-3.909c-2.254-1.439-4.277-2.743-6.069-3.911c-1.793-1.168-2.933-1.752-3.422-1.752c-3.313,0-6.341,0.544-9.084,1.63s-5.065,2.539-6.966,4.358c-1.901,1.82-3.585,4.02-5.051,6.6s-2.634,5.229-3.503,7.943c-0.869,2.716-1.589,5.662-2.159,8.84c-0.571,3.178-0.951,6.137-1.141,8.881c-0.19,2.744-0.285,5.554-0.285,8.433c0,6.517,1.983,11.664,5.948,15.439c3.965,3.774,9.234,5.661,15.806,5.661h71.208c6.572,0,11.84-1.887,15.806-5.661c3.966-3.775,5.948-8.921,5.948-15.439C165.357,111.591,165.262,108.78,165.07,106.037z"
                        style="fill: rgb(21, 140, 186);"
                    ></path>
                </g>
            </svg>
        </div>
    </label>
    <input
        id="lknCieloApiProPixBillingCpf"
        name="billing_cpf"
        class="input-text"
        type="text"
        pattern="[0-9]*"
        placeholder="<?php echo esc_attr('CPF / CNPJ'); ?>"
        maxlength="14"
        autocomplete="off"
        style="font-size: 1.5em; padding: 8px 45px;"
        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
        required
    />
</div>
<?php
        }

        $this->payment_gateway_scripts();
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $paymentComplete = true;
        try {
            // Verificação de nome
            $firstName = sanitize_text_field($first_name);
            $lastName = sanitize_text_field($last_name);
            $currency = (string) $order->get_currency();

            $fullName = $firstName . ' ' . $lastName;
            if (empty($fullName)) {
                throw new Exception('Nome não informado');
            }
            if ('' === $_POST['billing_cpf']) {
                $_POST['billing_cpf'] = $_POST['billing_cnpj'];
            }
            $billingCpfCpnj = array(
                'Identity' => sanitize_text_field($_POST['billing_cpf']),
                'IdentityType' => strlen($_POST['billing_cpf']) === 14 ? 'CPF' : 'CNPJ'
            );
            if ('' === $billingCpfCpnj['Identity'] || ! $this->validateCpfCnpj($billingCpfCpnj['Identity'])) {
                throw new Exception(__('Please enter a valid CPF or CNPJ.', 'lkn-wc-gateway-cielo'));
            }
            $amount = number_format((float) $order->get_total(), 2, '.', '');

            if ('BRL' != $currency) {
                $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency);
                $order->add_order_note('Amount converted: ' . $amount);
            }

            if (! $amount) {
                throw new Exception('Não foi possivel recuperar o valor da compra!', 1);
            }

            $response = self::$request->pix_request($fullName, $amount, $billingCpfCpnj, $this, $order);

            if (isset($response['sucess']) && $response['sucess'] === false) {
                throw new Exception(json_encode($response['response']), 1);
            }
            if (! is_array($response) && ! is_object($response)) {
                throw new Exception(json_encode($response), 1);
            }
            if (! $response['response']) {
                throw new Exception('Erro na Requisição. Tente novamente!', 1);
            }

            if (! wp_next_scheduled('lkn_schedule_check_payment_hook', array($response["response"]["paymentId"], $order_id))) {
                wp_schedule_event(time(), "every_minute", 'lkn_schedule_check_payment_hook', array($response["response"]["paymentId"], $order_id));
            }

            $order->update_meta_data('_wc_cielo_qrcode_image', $response['response']['qrcodeImage']);
            $order->update_meta_data('_wc_cielo_qrcode_string', $response['response']['qrcodeString']);
            $order->update_meta_data('_wc_cielo_qrcode_payment_id', $response['response']['paymentId']);

            $order->save();
        } catch (Exception $err) {
            $paymentComplete = false;
            $this->add_error($err->getMessage());
        }

        if ($paymentComplete) {
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        } else {
            $this->log->log('error', 'PIX Payment failed: ' . var_export($response, true), array('source' => 'woocommerce-cielo-pix'));
            $this->add_notice_once(__('PIX Payment Failed', 'lkn-wc-gateway-cielo-pro'), 'error');
            throw new Exception(esc_attr(__('PIX Payment Failed', 'lkn-wc-gateway-cielo-pro')));
        }
    }

    /**
     * Verify if WooCommerce notice exists before adding.
     *
     * @param string $message
     * @param string $type
     */
    private function add_notice_once($message, $type): void
    {
        if (! wc_has_notice($message, $type)) {
            wc_add_notice($message, $type);
        }
    }

    public function validateCpfCnpj($cpfCnpj)
    {
        // Remove caracteres especiais
        $cpfCnpj = preg_replace('/[^0-9]/', '', $cpfCnpj);

        // Verifica se é CPF
        if (strlen($cpfCnpj) === 11) {
            // Verifica se todos os dígitos são iguais
            if (preg_match('/(\d)\1{10}/', $cpfCnpj)) {
                return false;
            }

            // Calcula o primeiro dígito verificador
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * (10 - $i);
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            for ($i = 0; $i < 10; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * (11 - $i);
            }
            $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Verifica se os dígitos verificadores estão corretos
            if ($cpfCnpj[9] == $digit1 && $cpfCnpj[10] == $digit2) {
                return true;
            } else {
                return false;
            }
        }
        // Verifica se é CNPJ
        elseif (strlen($cpfCnpj) === 14) {
            // Verifica se todos os dígitos são iguais
            if (preg_match('/(\d)\1{13}/', $cpfCnpj)) {
                return false;
            }

            // Calcula o primeiro dígito verificador
            $sum = 0;
            $weights = array(5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * $weights[$i];
            }
            $digit1 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Calcula o segundo dígito verificador
            $sum = 0;
            $weights = array(6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2);
            for ($i = 0; $i < 13; $i++) {
                $sum += (int) ($cpfCnpj[$i]) * $weights[$i];
            }
            $digit2 = ($sum % 11 < 2) ? 0 : 11 - ($sum % 11);

            // Verifica se os dígitos verificadores estão corretos
            if ($cpfCnpj[12] == $digit1 && $cpfCnpj[13] == $digit2) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    public function add_error($message): void
    {
        global $woocommerce;

        $title = '<strong>' . esc_html($this->title) . ':</strong> ';

        if (function_exists('wc_add_notice')) {
            $message = wp_kses($message, array());
            throw new Exception(wp_kses_post("{$title} {$message}"));
        } else {
            $woocommerce->add_error($title . $message);
        }
    }

    // Agendar cron job

    public function payment_gateway_scripts(): void
    {
        // Don't load scripts outside payment page
        if (
            ! is_checkout()
            && ! isset($_GET['pay_for_order']) // wpcs: csrf ok.
            && ! is_add_payment_method_page()
            && ! isset($_GET['change_payment_method']) // wpcs: csrf ok.
            || is_order_received_page()
        ) {
            return;
        }

        // If is not enabled bail.
        if ('yes' !== $this->enabled) {
            return;
        }
    }

    public static function showPix($order_id): void
    {
        $order = wc_get_order($order_id);
        $paymentMethod = $order->get_payment_method();

        if ('lkn_wc_cielo_pix' === $paymentMethod) {
            $paymentId = $order->get_meta('_wc_cielo_qrcode_payment_id');
            $bas64Image = $order->get_meta('_wc_cielo_qrcode_image');
            $pixString = $order->get_meta('_wc_cielo_qrcode_string');
            wc_get_template('lkn-cielo-pix-template.php', array(
                'paymentId' => $paymentId,
                'pixString' => $pixString,
                'base64Image' => $bas64Image
            ), 'includes/templates', LKN_WC_GATEWAY_CIELO_DIR . 'includes/templates/');

            wp_enqueue_style('lkn-cielo-wc-payment-pix-style', LKN_WC_GATEWAY_CIELO_URL . 'resources/css/frontend/lkn-cielo-pix-style.css', array(), '1.0.0', 'all');

            wp_enqueue_script('lkn-cielo-wc-payment-pix-script', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/pix/lkn-cielo-pix-script.js', array(), '1.0.0', false);
            wp_localize_script('lkn-cielo-wc-payment-pix-script', 'phpVariables', array(
                'copiedText' => __('Copied!', 'lkn-wc-gateway-cielo'),
                'currentTheme' => wp_get_theme()->get('Name') ?? ''
            ));
        }
    }

    public function process_admin_options()
    {
        static $already_saved = false;

        if ($already_saved) {
            $already_saved = false;
            return false; // Impede a execução múltipla
        }

        $already_saved = true;

        // Obtém o valor antigo diretamente da opção no banco de dados
        $options = get_option('woocommerce_lkn_wc_cielo_pix_settings');
        $old_description = $options['description'] ?? '';
        $old_payment_complete_status = $options['payment_complete_status'] ?? '';
        $old_pix_layout = $options['pix_layout'] ?? '';
        $old_layout_location = $options['layout_location'] ?? '';

        $saved = parent::process_admin_options();

        if ($saved) {

            $new_description = $this->get_option('description');
            if ($new_description !== $old_description && !empty($old_description) && !empty($new_description)) {
                $this->update_option('description', $old_description);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . __('You are not allowed to modify this field (Description).', 'lkn-wc-gateway-cielo') . '</p>
                          </div>';
                });
            } else {
                $this->update_option('description', 'After the purchase is completed, the PIX will be generated and made available for payment!');
            }

            $new_payment_complete_status = $this->get_option('payment_complete_status');
            if ($new_payment_complete_status !== $old_payment_complete_status && !empty($old_payment_complete_status) && !empty($new_payment_complete_status)) {
                $this->update_option('payment_complete_status', $old_payment_complete_status);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . __('You are not allowed to modify this field (Payment Complete Status).', 'lkn-wc-gateway-cielo') . '</p>
                          </div>';
                });
            } else {
                $this->update_option('payment_complete_status', 'processing');
            }

            $new_pix_layout = $this->get_option('pix_layout');
            if ($new_pix_layout !== $old_pix_layout && !empty($old_pix_layout) && !empty($new_pix_layout)) {
                $this->update_option('pix_layout', $old_pix_layout);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . __('You are not allowed to modify this field (Pix Layout).', 'lkn-wc-gateway-cielo') . '</p>
                          </div>';
                });
            } else {
                $this->update_option('pix_layout', 'standard');
            }

            $new_layout_location = $this->get_option('layout_location');

            if ($new_layout_location !== $old_layout_location && !empty($old_layout_location) && !empty($new_layout_location)) {
                $this->update_option('layout_location', $old_layout_location);

                // Mostra a mensagem de erro
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible">
                            <p>' . __('You are not allowed to modify this field (Layout Location).', 'lkn-wc-gateway-cielo') . '</p>
                          </div>';
                });
            } else {
                $this->update_option('layout_location', 'bottom');
            }
        }

        return $saved;
    }
}
?>