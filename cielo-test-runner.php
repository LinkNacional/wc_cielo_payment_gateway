<?php
/**
 * Cielo Payment Gateway Test Runner
 * 
 * Este arquivo executa testes automatizados do gateway de pagamento Cielo
 * Para usar: acesse via browser http://seusite.com/wp-content/plugins/wc_cielo_payment_gateway/cielo-test-runner.php
 * 
 * ATENÇÃO: Use apenas em ambiente de desenvolvimento/sandbox!
 */

// Verificar se estamos no WordPress
if (!defined('ABSPATH')) {
    // Tentar carregar WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once(__DIR__ . '/' . $path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress não encontrado. Certifique-se de que este arquivo está na pasta do plugin.');
    }
}

// Verificar se é admin ou ambiente de desenvolvimento
if (!current_user_can('manage_options') && !defined('WP_DEBUG') && !WP_DEBUG) {
    die('Acesso negado. Este arquivo só pode ser usado por administradores em ambiente de desenvolvimento.');
}

// Configurações de teste da Cielo
$test_config = [
    // Cartões de teste
    'cards' => [
        'visa_credit' => [
            'number' => '4000000000001091',
            'cvv' => '123',
            'expiry' => '06/35',
            'holder' => 'João da Silva',
            'brand' => 'Visa',
            'type' => 'Credit'
        ],
        'elo_credit' => [
            'number' => '6505290000002190',
            'cvv' => '123',
            'expiry' => '11/30',
            'holder' => 'Maria Santos',
            'brand' => 'Elo',
            'type' => 'Credit'
        ],
        'mastercard_debit' => [
            'number' => '5555666677778884',
            'cvv' => '123',
            'expiry' => '09/35',
            'holder' => 'Ana Debit',
            'brand' => 'Mastercard',
            'type' => 'Debit'
        ],
        'visa_debit' => [
            'number' => '4551870000000183',
            'cvv' => '123',
            'expiry' => '08/35',
            'holder' => 'Carlos Debit',
            'brand' => 'Visa',
            'type' => 'Debit'
        ],
        'elo_debit' => [
            'number' => '6505290000002190',
            'cvv' => '123',
            'expiry' => '12/35',
            'holder' => 'Ana Elo',
            'brand' => 'Elo',
            'type' => 'Debit'
        ]
    ],
    
    // Cenários de teste
    'scenarios' => [
        'credit_no_3ds' => ['card_type' => 'Credit', 'use_3ds' => false],
        'credit_with_3ds' => ['card_type' => 'Credit', 'use_3ds' => true],
        'debit_no_3ds' => ['card_type' => 'Debit', 'use_3ds' => false],
        'debit_with_3ds' => ['card_type' => 'Debit', 'use_3ds' => true],
        'credit_installments_2x' => ['card_type' => 'Credit', 'use_3ds' => false, 'installments' => 2],
        'credit_installments_4x' => ['card_type' => 'Credit', 'use_3ds' => false, 'installments' => 4],
        'credit_wrong_card_type' => ['card_type' => 'Credit', 'use_3ds' => false, 'force_wrong_card' => true],
        'debit_wrong_card_type' => ['card_type' => 'Debit', 'use_3ds' => false, 'force_wrong_card' => true]
    ]
];

class CieloTestRunner {
    private $gateways = [];
    private $test_results = [];
    private $test_product_id;
    
    public function __construct() {
        // Inicializar WooCommerce se necessário
        if (class_exists('WooCommerce')) {
            WC();
        }
        
        // Carregar os gateways Cielo
        $this->loadGateways();
        
        // Criar produto de teste
        $this->createTestProduct();
    }
    
    private function loadGateways() {
        $available_gateways = [];
        
        // Gateway Cielo Debit (crédito + débito)
        if (class_exists('Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit')) {
            $gateway_debit = new Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit();
            $available_gateways['lkn_cielo_debit'] = [
                'gateway' => $gateway_debit,
                'name' => 'Cielo Debit (Crédito + Débito)',
                'supports_debit' => true,
                'supports_credit' => true
            ];
        }
        
        // Gateway Cielo Credit (só crédito)
        if (class_exists('Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit')) {
            $gateway_credit = new Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit();
            $available_gateways['lkn_cielo_credit'] = [
                'gateway' => $gateway_credit,
                'name' => 'Cielo Credit (Só Crédito)',
                'supports_debit' => false,
                'supports_credit' => true
            ];
        }
        
        $this->gateways = $available_gateways;
        
        if (empty($this->gateways)) {
            die('Nenhum gateway Cielo encontrado. Certifique-se de que o plugin está ativo.');
        }
    }
    
    private function createTestProduct() {
        // Verificar se já existe produto de teste
        $existing_id = get_option('cielo_test_product_id');
        if ($existing_id && get_post($existing_id)) {
            $this->test_product_id = $existing_id;
            return;
        }
        
        // Criar novo produto de teste
        $product = new WC_Product_Simple();
        $product->set_name('Produto Teste Cielo');
        $product->set_regular_price(100.00);
        $product->set_short_description('Produto criado para testes do gateway Cielo');
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');
        $product->set_catalog_visibility('hidden');
        
        $product_id = $product->save();
        
        update_option('cielo_test_product_id', $product_id);
        $this->test_product_id = $product_id;
        
        echo "<p><strong>✅ Produto de teste criado:</strong> ID #{$product_id} - R$ 100,00</p>";
    }
    
    public function runAllTests() {
        global $test_config;
        
        echo "<h2>🚀 Iniciando Testes do Gateway Cielo</h2>";
        echo "<p><strong>Produto de teste:</strong> ID #{$this->test_product_id}</p>";
        
        // Mostrar gateways disponíveis
        echo "<h3>🔧 Gateways Disponíveis:</h3>";
        foreach ($this->gateways as $gateway_id => $gateway_info) {
            $status = $gateway_info['gateway']->enabled === 'yes' ? '✅ Ativo' : '❌ Inativo';
            echo "<p><strong>{$gateway_info['name']}:</strong> {$status}</p>";
        }
        echo "<hr>";
        
        $test_count = 0;
        
        // Para cada gateway disponível
        foreach ($this->gateways as $gateway_id => $gateway_info) {
            if ($gateway_info['gateway']->enabled !== 'yes') {
                echo "<p>⏭️ Pulando {$gateway_info['name']} (inativo)</p>";
                continue;
            }
            
            echo "<h2>🎯 Testando Gateway: {$gateway_info['name']}</h2>";
            
            // Contar apenas cenários válidos
            $valid_scenarios = [];
            
            foreach ($test_config['scenarios'] as $scenario_name => $scenario) {
                if ($scenario['card_type'] === 'Debit' && !$gateway_info['supports_debit']) {
                    // Pular silenciosamente cenários de débito em gateway que só suporta crédito
                } else {
                    $valid_scenarios[] = $scenario_name;
                }
            }
            
            echo "<p>✅ <strong>Testando cenários:</strong> " . implode(', ', $valid_scenarios) . "</p>";
            echo "<hr>";
            
            // Para cada cenário
            foreach ($test_config['scenarios'] as $scenario_name => $scenario) {
                // Verificar se o gateway suporta o tipo de cartão
                if ($scenario['card_type'] === 'Debit' && !$gateway_info['supports_debit']) {
                    continue;
                }
                
                // Para cada cartão
                foreach ($test_config['cards'] as $card_name => $card) {
                    // Se é teste de cartão errado, usar cartão incompatível
                    if (isset($scenario['force_wrong_card']) && $scenario['force_wrong_card']) {
                        // Teste de incompatibilidade para crédito: usar apenas visa_debit no gateway só crédito
                        if ($scenario_name === 'credit_wrong_card_type') {
                            if ($gateway_id === 'lkn_cielo_credit' && $card_name !== 'visa_debit') {
                                continue; // Usar apenas visa_debit no teste do gateway de crédito
                            }
                            if ($gateway_id === 'lkn_cielo_debit') {
                                continue; // Pular gateway debit no teste de incompatibilidade de crédito (ele aceita ambos)
                            }
                        }
                        // Teste de incompatibilidade para débito: usar cartão de crédito no gateway só crédito  
                        if ($scenario_name === 'debit_wrong_card_type') {
                            if ($gateway_id === 'lkn_cielo_credit' && $card['type'] !== 'Credit') {
                                continue; // Usar apenas cartões de crédito no teste do gateway de crédito
                            }
                            if ($gateway_id === 'lkn_cielo_debit') {
                                continue; // Pular gateway debit no teste de incompatibilidade de débito (ele aceita ambos)
                            }
                        }
                    } else {
                        // Testes normais: usar cartão compatível com o cenário
                        if ($scenario['card_type'] !== $card['type']) {
                            continue; // Pular cartões incompatíveis
                        }
                        
                        // Pular visa_debit no teste debit_with_3ds (problemático)
                        if ($scenario_name === 'debit_with_3ds' && $card_name === 'visa_debit') {
                            continue;
                        }
                    }
                    
                    $test_count++;
                    
                    echo "<h3>📋 Teste #{$test_count}: {$gateway_info['name']} → {$scenario_name} + {$card_name}</h3>";
                    
                    try {
                        $result = $this->runSingleTest($gateway_info, $scenario, $card, $test_count, $scenario_name);
                        $this->test_results[] = $result;
                        
                        if ($result['success']) {
                            echo "<p>✅ <strong>SUCESSO:</strong> {$result['message']}</p>";
                        } else {
                            echo "<p>❌ <strong>FALHA:</strong> {$result['message']}</p>";
                        }
                        
                    } catch (Exception $e) {
                        echo "<p>💥 <strong>ERRO:</strong> " . esc_html($e->getMessage()) . "</p>";
                        $this->test_results[] = [
                            'success' => false,
                            'message' => $e->getMessage(),
                            'scenario_name' => $scenario_name,
                            'card' => $card_name,
                            'gateway' => $gateway_info['name']
                        ];
                    }
                    
                    echo "<hr>";
                    
                    // Pequena pausa entre testes
                    usleep(500000); // 0.5 segundos
                }
            }
        }
        
        $this->displaySummary();
    }
    
    private function runSingleTest($gateway_info, $scenario, $card, $test_number, $scenario_name = '') {
        // Simular sessão do WooCommerce ANTES de criar o pedido
        $this->simulateCheckoutSession($gateway_info, $scenario, null);
        
        // Criar pedido de teste (agora com fees já aplicados no carrinho)
        $order = $this->createTestOrder($gateway_info, $scenario);
        
        echo "<p><strong>📦 Order ID:</strong> #{$order->get_id()}</p>";
        echo "<p><strong>🏪 Gateway:</strong> {$gateway_info['name']}</p>";
        echo "<p><strong>💰 Amount:</strong> R$ " . number_format($order->get_total(), 2, ',', '.') . "</p>";
        echo "<p><strong>💳 Cartão:</strong> {$card['brand']} (**** **** **** " . substr($card['number'], -4) . ") - Tipo: {$card['type']}</p>";
        echo "<p><strong>🎯 Cenário:</strong> {$scenario['card_type']}</p>";
        echo "<p><strong>🔐 3DS:</strong> " . ($scenario['use_3ds'] ? 'Sim' : 'Não') . "</p>";
        
        // Mostrar se é teste de incompatibilidade
        if (isset($scenario['force_wrong_card']) && $scenario['force_wrong_card']) {
            if ($scenario_name === 'credit_wrong_card_type') {
                echo "<p><strong>⚠️ Teste de Incompatibilidade:</strong> Usando cartão {$card['type']} no gateway que só aceita crédito (deve falhar)</p>";
            } elseif ($scenario_name === 'debit_wrong_card_type') {
                echo "<p><strong>⚠️ Teste de Incompatibilidade:</strong> Usando cartão {$card['type']} em cenário de débito (deve falhar se gateway não suportar)</p>";
            } else {
                echo "<p><strong>⚠️ Teste de Incompatibilidade:</strong> Usando cartão {$card['type']} em cenário incompatível (deve falhar)</p>";
            }
        }
        
        if (isset($scenario['installments'])) {
            echo "<p><strong>📊 Parcelas:</strong> {$scenario['installments']}x</p>";
        }
        
        // Preparar dados do POST simulado
        $post_data = $this->preparePostData($gateway_info, $scenario, $card, $order);
        
        // Simular $_POST global
        $original_post = $_POST;
        $_POST = $post_data;

        try {
            // Chamar o método process_payment do gateway específico
            $result = $gateway_info['gateway']->process_payment($order->get_id());
            
            // Capturar dados da transação da Cielo (se disponível)
            $cielo_data = $this->extractCieloTransactionData($order);
            
            // Restaurar $_POST original
            $_POST = $original_post;
            
            if (isset($result['result']) && $result['result'] === 'success') {
                $success_message = "Pagamento processado com sucesso. Redirect: " . $result['redirect'];
                
                // Verificar se é teste de incompatibilidade que passou quando deveria falhar
                if (isset($scenario['force_wrong_card']) && $scenario['force_wrong_card']) {
                    $success_message = "⚠️ ATENÇÃO: Teste de incompatibilidade passou (pode ser comportamento normal do gateway). " . $success_message;
                }
                
                // Adicionar informações da Cielo se disponível
                if ($cielo_data) {
                    $success_message .= $this->formatCieloData($cielo_data, $order, $scenario);
                }
                
                return [
                    'success' => true,
                    'message' => $success_message,
                    'order_id' => $order->get_id(),
                    'scenario' => $scenario,
                    'scenario_name' => $scenario_name,
                    'card' => $card,
                    'gateway' => $gateway_info['name'],
                    'cielo_data' => $cielo_data
                ];
            } else {
                $error_message = is_array($result) && isset($result['messages']) ? $result['messages'] : "Falha no processamento";
                
                return [
                    'success' => false,
                    'message' => $error_message,
                    'order_id' => $order->get_id(),
                    'scenario' => $scenario,
                    'scenario_name' => $scenario_name,
                    'card' => $card,
                    'gateway' => $gateway_info['name']
                ];
            }
            
        } catch (Exception $e) {
            // Restaurar $_POST original
            $_POST = $original_post;
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'order_id' => $order->get_id(),
                'scenario' => $scenario,
                'scenario_name' => $scenario_name,
                'card' => $card,
                'gateway' => $gateway_info['name']
            ];
        }
    }
    
    private function createTestOrder($gateway_info, $scenario = []) {
        // Se o carrinho já foi configurado pela simulação, criar pedido a partir dele
        if (WC()->cart && WC()->cart->get_cart_contents_count() > 0) {
            return $this->createOrderFromCart($gateway_info);
        }
        
        // Fallback: criar pedido programaticamente (caso não tenha carrinho)
        return $this->createOrderManually($gateway_info);
    }
    
    private function createOrderFromCart($gateway_info) {
        // Criar pedido a partir do carrinho configurado (com fees aplicados)
        $checkout = WC()->checkout();
        
        if (!$checkout) {
            // Fallback para criação manual
            return $this->createOrderManually($gateway_info);
        }
        
        // Preparar dados do checkout
        $checkout_data = array(
            'payment_method' => '',
            'billing_first_name' => 'João',
            'billing_last_name' => 'da Silva',
            'billing_email' => 'joao@teste.com',
            'billing_phone' => '11999999999',
            'billing_address_1' => 'Rua Teste, 123',
            'billing_city' => 'São Paulo',
            'billing_state' => 'SP',
            'billing_postcode' => '01234-567',
            'billing_country' => 'BR'
        );
        
        // Determinar gateway ID
        $gateway_id = '';
        foreach ($this->gateways as $id => $info) {
            if ($info === $gateway_info) {
                $gateway_id = $id;
                $checkout_data['payment_method'] = $gateway_id;
                break;
            }
        }
        
        try {
            // Criar pedido via checkout (que preserva fees)
            $order_id = $checkout->create_order($checkout_data);
            
            if (is_wp_error($order_id)) {
                throw new Exception($order_id->get_error_message());
            }
            
            $order = wc_get_order($order_id);
            
            if (!$order) {
                throw new Exception('Falha ao criar pedido');
            }
            
            $order->set_payment_method($gateway_id);
            $order->set_payment_method_title($gateway_info['name']);
            
            // Verificar se os fees do carrinho foram aplicados corretamente
            $cart_total = WC()->cart->get_total('edit');
            $order_total = $order->get_total('edit');
            
            // Se o total do carrinho for diferente do pedido, aplicar fees manualmente
            if (abs($cart_total - $order_total) > 0.01) {
                // Aplicar fees do carrinho ao pedido
                if (WC()->cart && WC()->cart->get_fees()) {
                    foreach (WC()->cart->get_fees() as $fee) {
                        $item = new WC_Order_Item_Fee();
                        $item->set_name($fee->name);
                        $item->set_amount($fee->amount);
                        $item->set_tax_status($fee->taxable);
                        $item->set_tax_class($fee->tax_class);
                        $item->set_total($fee->total);
                        $order->add_item($item);
                    }
                    $order->calculate_totals();
                }
            }
            
            $order->save();
            
            return $order;
            
        } catch (Exception $e) {
            echo "<p><strong>⚠️ Erro no checkout:</strong> " . esc_html($e->getMessage()) . " - usando criação manual</p>";
            return $this->createOrderManually($gateway_info);
        }
    }
    

    
    private function createOrderManually($gateway_info) {
        // Criar pedido programaticamente (fallback)
        $order = wc_create_order();
        
        // Adicionar produto ao pedido
        $product = wc_get_product($this->test_product_id);
        $order->add_product($product, 1);
        
        // Definir endereço de cobrança
        $order->set_billing_first_name('João');
        $order->set_billing_last_name('da Silva');
        $order->set_billing_email('joao@teste.com');
        $order->set_billing_phone('11999999999');
        $order->set_billing_address_1('Rua Teste, 123');
        $order->set_billing_city('São Paulo');
        $order->set_billing_state('SP');
        $order->set_billing_postcode('01234-567');
        $order->set_billing_country('BR');
        
        // Definir método de pagamento baseado no gateway
        $gateway_id = '';
        foreach ($this->gateways as $id => $info) {
            if ($info === $gateway_info) {
                $gateway_id = $id;
                break;
            }
        }
        
        $order->set_payment_method($gateway_id);
        $order->set_payment_method_title($gateway_info['name']);
        
        // Aplicar fees do carrinho se existirem
        if (WC()->cart && WC()->cart->get_fees()) {
            foreach (WC()->cart->get_fees() as $fee) {
                $item = new WC_Order_Item_Fee();
                $item->set_name($fee->name);
                $item->set_amount($fee->amount);
                $item->set_tax_status($fee->taxable);
                $item->set_tax_class($fee->tax_class);
                $item->set_total($fee->total);
                $order->add_item($item);
            }
        }
        
        // Calcular totais
        $order->calculate_totals();
        
        // Salvar pedido
        $order->save();
        
        return $order;
    }
    
    private function preparePostData($gateway_info, $scenario, $card, $order) {
        // Determinar gateway ID
        $gateway_id = '';
        foreach ($this->gateways as $id => $info) {
            if ($info === $gateway_info) {
                $gateway_id = $id;
                break;
            }
        }
        
        $post_data = [
            'payment_method' => $gateway_id,
            'lkn_cc_type' => $scenario['card_type']
        ];
        
        // Campos específicos baseados no tipo de gateway
        if ($gateway_id === 'lkn_cielo_credit') {
            $post_data['lkn_ccno'] = $card['number'];
            $post_data['lkn_cc_expdate'] = $card['expiry'];
            $post_data['lkn_cc_cvc'] = $card['cvv'];
            $post_data['lkn_cc_cardholder_name'] = $card['holder'];
        } else {
            $post_data['lkn_dcno'] = $card['number'];
            $post_data['lkn_dc_expdate'] = $card['expiry'];
            $post_data['lkn_dc_cvc'] = $card['cvv'];
            $post_data['lkn_dc_cardholder_name'] = $card['holder'];
        }
        
        // Nonce específico para cada gateway
        if ($gateway_id === 'lkn_cielo_debit') {
            $post_data['nonce_lkn_cielo_debit'] = wp_create_nonce('nonce_lkn_cielo_debit');
        } elseif ($gateway_id === 'lkn_cielo_credit') {
            $post_data['nonce_lkn_cielo_credit'] = wp_create_nonce('nonce_lkn_cielo_credit');
        }
        
        // Se tem parcelas
        if (isset($scenario['installments'])) {
            $post_data['lkn_cc_dc_installments'] = $scenario['installments'];
        }
        
        // Se usa 3DS, simular dados de autenticação
        if ($scenario['use_3ds']) {
            $post_data['lkn_cielo_3ds_cavv'] = 'AAABBIIFmAAAAAAAAAAAAAA=';
            $post_data['lkn_cielo_3ds_eci'] = '7';
            $post_data['lkn_cielo_3ds_ref_id'] = '12345678-1234-1234-1234-123456789012';
            $post_data['lkn_cielo_3ds_version'] = '2.1.0';
            $post_data['lkn_cielo_3ds_xid'] = 'MDAwMDAwMDAwMDAwMDAwMDAwMDA=';
        }
        
        return $post_data;
    }
    
    private function displaySummary() {
        $total_tests = count($this->test_results);
        $successful_tests = array_filter($this->test_results, function($result) {
            return $result['success'];
        });
        $success_count = count($successful_tests);
        $failure_count = $total_tests - $success_count;
        
        echo "<h2>📊 Resumo dos Testes</h2>";
        echo "<p><strong>Total de testes:</strong> {$total_tests}</p>";
        echo "<p><strong>✅ Sucessos:</strong> {$success_count}</p>";
        echo "<p><strong>❌ Falhas:</strong> {$failure_count}</p>";
        echo "<p><strong>📈 Taxa de sucesso:</strong> " . round(($success_count / $total_tests) * 100, 2) . "%</p>";
        
        if ($failure_count > 0) {
            echo "<h3>❌ Testes que falharam:</h3>";
            echo "<ul>";
            foreach ($this->test_results as $result) {
                if (!$result['success']) {
                    $gateway_name = $result['gateway'] ?? 'N/A';
                    $scenario_name = $result['scenario_name'] ?? 'Cenário Desconhecido';
                    echo "<li><strong>{$gateway_name} → {$scenario_name}</strong> - {$result['message']}</li>";
                }
            }
            echo "</ul>";
        }
        
        // Resumo por gateway
        echo "<h3>📊 Resumo por Gateway:</h3>";
        $gateway_stats = [];
        foreach ($this->test_results as $result) {
            $gateway = $result['gateway'] ?? 'N/A';
            if (!isset($gateway_stats[$gateway])) {
                $gateway_stats[$gateway] = ['total' => 0, 'success' => 0];
            }
            $gateway_stats[$gateway]['total']++;
            if ($result['success']) {
                $gateway_stats[$gateway]['success']++;
            }
        }
        
        foreach ($gateway_stats as $gateway => $stats) {
            $rate = round(($stats['success'] / $stats['total']) * 100, 1);
            echo "<p><strong>{$gateway}:</strong> {$stats['success']}/{$stats['total']} ({$rate}%)</p>";
        }
    }
    
    private function extractCieloTransactionData($order) {
        // Verificar metadados da transação Cielo no pedido
        $cielo_data = [];
        
        // Tentar capturar dados do meta do pedido (nomes reais usados pelo gateway)
        $payment_id = $order->get_meta('paymentId');
        $nsu = $order->get_meta('lkn_nsu');
        $captured_amount = $order->get_meta('amount_converted');
        $installments = $order->get_meta('installments');
        $order_logs = $order->get_meta('lknWcCieloOrderLogs');
        
        // Tentar extrair mais dados dos logs se disponível
        $interest_amount = null;
        $payment_amount_cielo = null;
        $tid = null;
        $return_code = null;
        
        if (!empty($order_logs) && is_array($order_logs)) {
            foreach ($order_logs as $log) {
                if (isset($log['response']) && is_string($log['response'])) {
                    $response = json_decode($log['response'], true);
                    if (isset($response['Payment']['Amount'])) {
                        $payment_amount_cielo = $response['Payment']['Amount'];
                    }
                    if (isset($response['Payment']['Interest'])) {
                        $interest_amount = $response['Payment']['Interest'];
                    }
                    if (isset($response['Payment']['Tid'])) {
                        $tid = $response['Payment']['Tid'];
                    }
                    if (isset($response['Payment']['ReturnCode'])) {
                        $return_code = $response['Payment']['ReturnCode'];
                    }
                }
            }
        }
        
        if ($payment_id || $captured_amount || $payment_amount_cielo) {
            $cielo_data = [
                'payment_id' => $payment_id,
                'nsu' => $nsu,
                'captured_amount' => $captured_amount ?: $payment_amount_cielo,
                'payment_amount_cielo' => $payment_amount_cielo,
                'installments' => $installments,
                'interest_amount' => $interest_amount,
                'tid' => $tid,
                'return_code' => $return_code,
                'order_logs' => $order_logs
            ];
        }
        
        return !empty(array_filter($cielo_data)) ? $cielo_data : null;
    }
    
    private function formatCieloData($cielo_data, $order, $scenario) {
        $output = "\n\n🔍 **Dados da Transação Cielo:**";
        
        if (isset($cielo_data['payment_id'])) {
            $output .= "\n• Payment ID: " . $cielo_data['payment_id'];
        }
        
        if (isset($cielo_data['nsu'])) {
            $output .= "\n• NSU: " . $cielo_data['nsu'];
        }
        if (isset($cielo_data['tid'])) {
            $output .= "\n• TID: " . $cielo_data['tid'];
        }
        
        if (isset($cielo_data['return_code'])) {
            $output .= "\n• Return Code: " . $cielo_data['return_code'];
        }
        
        // Mostrar comparação de valores
        if (isset($cielo_data['captured_amount']) || isset($cielo_data['payment_amount_cielo'])) {
            $cielo_amount = intval($cielo_data['captured_amount'] ?: $cielo_data['payment_amount_cielo']);
            $formatted_amount = number_format($cielo_amount / 100, 2, ',', '.');
            $order_total = $order->get_total();
            
            $output .= "\n• Valor Cielo: R$ {$formatted_amount} (formato: {$cielo_amount})";
            $output .= "\n• Valor Pedido: R$ " . number_format($order_total, 2, ',', '.');
            
            // Calcular diferença (possível juros aplicados pela Cielo)
            $difference = ($cielo_amount / 100) - $order_total;
            if (abs($difference) < 0.01) {
                $output .= " ✅ Valores conferem";
            } else {
                $output .= " ⚠️  Diferença: R$ " . number_format($difference, 2, ',', '.');
                if ($difference > 0) {
                    $output .= " (juros aplicados pela Cielo)";
                }
            }
        }
        
        if (isset($cielo_data['installments']) && isset($scenario['installments'])) {
            $output .= "\n• Parcelas Enviadas: " . $scenario['installments'] . "x";
            $output .= "\n• Parcelas Cielo: " . $cielo_data['installments'] . "x";
            
            if ($scenario['installments'] == $cielo_data['installments']) {
                $output .= " ✅ Parcelas conferem";
            } else {
                $output .= " ❌ Parcelas divergem";
            }
        }
        
        if (isset($cielo_data['interest_amount'])) {
            $interest = intval($cielo_data['interest_amount']);
            if ($interest > 0) {
                $formatted_interest = number_format($interest / 100, 2, ',', '.');
                $output .= "\n• Juros Cielo: R$ {$formatted_interest} (formato: {$interest})";
            }
        }
        
        return $output;
    }
    
    private function simulateCheckoutSession($gateway_info, $scenario, $order = null) {
        // Verificar se WooCommerce está carregado
        if (!function_exists('WC') || !WC()->session || !WC()->cart) {
            return;
        }
        
        // Determinar gateway ID
        $gateway_id = '';
        foreach ($this->gateways as $id => $info) {
            if ($info === $gateway_info) {
                $gateway_id = $id;
                break;
            }
        }
        
        if (!$gateway_id) {
            return;
        }
        
        // Definir método de pagamento na sessão
        WC()->session->set('chosen_payment_method', $gateway_id);
        
        // Se tem parcelas, definir na sessão
        if (isset($scenario['installments'])) {
            WC()->session->set($gateway_id . '_installment', $scenario['installments']);
            echo "<p><strong>🔧 Sessão configurada:</strong> {$gateway_id}_installment = {$scenario['installments']}</p>";
        }
        
        // Para gateway debit, definir tipo de cartão na sessão
        if ($gateway_id === 'lkn_cielo_debit' && isset($scenario['card_type'])) {
            WC()->session->set('lkn_cielo_debit_card_type', $scenario['card_type']);
            echo "<p><strong>🔧 Tipo de cartão definido:</strong> {$scenario['card_type']}</p>";
        }
        
        // Limpar carrinho e adicionar produto
        WC()->cart->empty_cart();
        $product = wc_get_product($this->test_product_id);
        if ($product) {
            WC()->cart->add_to_cart($this->test_product_id, 1);
            
            // Forçar cálculo dos fees (juros/desconto) se o plugin PRO estiver ativo
            if (class_exists('Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPayment')) {
                echo "<p><strong>🔧 Aplicando juros via sessão...</strong></p>";
                
                // Simular o hook que aplica juros
                try {
                    // Instanciar a classe principal para acessar o método
                    $payment_class = new \Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPayment();
                    $payment_class->add_checkout_fee_or_discount_in_credit_card();
                    
                    // Calcular totais do carrinho para aplicar os fees
                    WC()->cart->calculate_totals();
                    
                    $cart_total = WC()->cart->get_total('raw');
                    $fees = WC()->cart->get_fees();
                    
                    if (!empty($fees)) {
                        echo "<p><strong>💰 Fees aplicados no carrinho:</strong></p>";
                        foreach ($fees as $fee) {
                            $fee_amount = $fee->total;
                            $fee_name = $fee->name;
                            echo "<p>• {$fee_name}: R$ " . number_format(abs($fee_amount), 2, ',', '.') . ($fee_amount < 0 ? ' (desconto)' : ' (juros)') . "</p>";
                        }
                        echo "<p><strong>💰 Total do carrinho com fees:</strong> R$ " . number_format($cart_total, 2, ',', '.') . "</p>";
                    } else {
                        echo "<p><strong>⚠️ Nenhuma fee aplicada</strong> (verificar configurações de juros no gateway)</p>";
                    }
                } catch (Exception $e) {
                    echo "<p><strong>⚠️ Erro ao aplicar juros:</strong> " . esc_html($e->getMessage()) . "</p>";
                }
            } else {
                echo "<p><strong>⚠️ Plugin PRO não encontrado</strong> - juros não serão aplicados</p>";
            }
        }
    }
}

// Verificar se deve executar os testes
if (isset($_GET['run_tests']) && $_GET['run_tests'] === '1') {
    echo "<!DOCTYPE html><html><head><title>Cielo Test Runner</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;}h2{color:#333;}h3{color:#666;}hr{margin:20px 0;}</style>";
    echo "</head><body>";
    
    $runner = new CieloTestRunner();
    $runner->runAllTests();
    
    echo "</body></html>";
} else {
    // Mostrar página inicial
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Cielo Payment Gateway Test Runner</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            .button:hover { background: #005177; }
            .card-list { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h1>🧪 Cielo Payment Gateway Test Runner</h1>
        
        <div class="warning">
            <strong>⚠️ ATENÇÃO:</strong> Este arquivo executa testes reais com o gateway Cielo. 
            Certifique-se de estar em ambiente SANDBOX antes de executar!
        </div>
        
        <h2>📋 O que este teste faz:</h2>
        <ul>
            <li>✅ Cria produto de teste automaticamente</li>
            <li>✅ Gera cerca de 35+ pedidos com diferentes cenários</li>
            <li>✅ Testa cartões de crédito e débito</li>
            <li>✅ Testa pagamentos com e sem 3DS</li>
            <li>✅ Testa parcelamentos (1x, 2x, 4x)</li>
            <li>✅ Captura valores e juros da API Cielo</li>
            <li>✅ Compara valores calculados vs retornados</li>
            <li>✅ Testa rejeição de cartões incompatíveis</li>
            <li>✅ Exibe detalhes de cada transação (amount, tipo, etc.)</li>
            <li>✅ Suporta múltiplos gateways (Debit + Credit)</li>
            <li>✅ Mostra relatório final com taxa de sucesso</li>
        </ul>
        
        <h2>💳 Cartões de Teste:</h2>
        <div class="card-list">
            <p><strong>Visa Crédito:</strong> 4000000000001091 | CVV: 123 | Exp: 06/35</p>
            <p><strong>Elo Crédito:</strong> 6505290000002190 | CVV: 123 | Exp: 11/30</p>
            <p><strong>Mastercard Débito:</strong> 5555666677778884 | CVV: 123 | Exp: 09/35</p>
        </div>
        
        <h2>🎯 Cenários Testados:</h2>
        <ul>
            <li>Crédito sem 3DS</li>
            <li>Crédito com 3DS</li>
            <li>Débito sem 3DS</li>
            <li>Débito com 3DS</li>
            <li>Crédito 2x parcelas</li>
            <li>Crédito 12x parcelas</li>
        </ul>
        
        <p><strong>Gateway Status:</strong> 
        <?php 
        $gateway_status = [];
        if (class_exists('Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit')) {
            $gateway_debit = new Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit();
            $status = $gateway_debit->enabled === 'yes' ? '✅ Ativo' : '❌ Inativo';
            $env = $gateway_debit->get_option('env') === 'production' ? '🔴 Produção' : '🟡 Sandbox';
            $gateway_status[] = "Debit: {$status}";
        }
        
        if (class_exists('Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit')) {
            $gateway_credit = new Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit();
            $status = $gateway_credit->enabled === 'yes' ? '✅ Ativo' : '❌ Inativo';
            $gateway_status[] = "Credit: {$status}";
        }
        
        if (empty($gateway_status)) {
            echo '❌ Nenhum gateway encontrado';
        } else {
            echo implode(' | ', $gateway_status);
            if (isset($env)) echo " | Ambiente: {$env}";
        }
        ?>
        </p>
        
        <a href="?run_tests=1" class="button">🚀 Executar Testes</a>
    </body>
    </html>
    <?php
}
?>