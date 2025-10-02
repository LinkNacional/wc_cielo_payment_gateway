<?php
/**
 * Arquivo de verificação do plugin - pode ser acessado via WordPress
 * URL: /wp-content/plugins/wc_cielo_payment_gateway/verify-plugin.php
 */

// Carrega WordPress se não estiver carregado
if (!function_exists('get_option')) {
    require_once '../../../wp-load.php';
}

// Verificar se a função is_plugin_active existe
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Headers para evitar cache
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

echo "=== VERIFICAÇÃO DO PLUGIN CIELO ===\n\n";

// Verificar se o autoloader existe
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "✅ Autoloader encontrado\n";
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    echo "❌ Autoloader NÃO encontrado\n";
    exit;
}

// Verificar classes principais
$classes_to_check = [
    'Lkn\\WcCieloPaymentGateway\\Includes\\Lkn_Wc_Cielo_Payment_Gateway',
    'Lkn\\WcCieloPaymentGateway\\Includes\\Lkn_Wc_Gateway_Cielo_Credit',
    'Lkn\\WcCieloPaymentGateway\\Includes\\Lkn_Wc_Cielo_Pix',
    'Lkn\\WcCieloPaymentGateway\\Includes\\Lkn_Wc_Cielo_Pix_Blocks'
];

echo "\n=== VERIFICAÇÃO DE CLASSES ===\n";
foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "✅ $class\n";
    } else {
        echo "❌ $class\n";
    }
}

// Verificar se o plugin está ativo no WordPress
echo "\n=== VERIFICAÇÃO NO WORDPRESS ===\n";
if (function_exists('is_plugin_active') && is_plugin_active('wc_cielo_payment_gateway/lkn-wc-cielo-payment-gateway.php')) {
    echo "✅ Plugin está ATIVO no WordPress\n";
} else {
    echo "❌ Plugin NÃO está ativo no WordPress\n";
}

// Verificar WooCommerce
if (class_exists('WooCommerce')) {
    echo "✅ WooCommerce está carregado\n";
} else {
    echo "❌ WooCommerce NÃO está carregado\n";
}

// Verificar gateways de pagamento
if (function_exists('WC') && WC()->payment_gateways()) {
    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    $cielo_gateways = array_filter($gateways, function($gateway) {
        return strpos(get_class($gateway), 'Cielo') !== false;
    });
    
    echo "\n=== GATEWAYS CIELO REGISTRADOS ===\n";
    if (count($cielo_gateways) > 0) {
        foreach ($cielo_gateways as $gateway) {
            echo "✅ " . get_class($gateway) . " - " . $gateway->get_title() . "\n";
        }
    } else {
        echo "❌ Nenhum gateway Cielo encontrado\n";
    }
} else {
    echo "❌ WooCommerce payment gateways não disponível\n";
}

echo "\n=== VERIFICAÇÃO COMPLETA ===\n";
