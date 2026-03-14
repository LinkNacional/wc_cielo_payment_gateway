# Resumo Executivo - Mapeamento de Acoplamento

## 📋 Documento Principal
Veja o relatório técnico completo em: **[MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md)**

---

## 🎯 Objetivo
Mapear o nível de acoplamento do plugin WooCommerce Cielo Payment Gateway com WordPress/WooCommerce para criar estratégia de testes unitários com PHPUnit e Brain\Monkey.

---

## 📊 Nível de Acoplamento: MÉDIO-ALTO

### Fortemente Acoplado ✅
- **WordPress HTTP API**: `wp_remote_post()`, `wp_remote_get()` em 8 pontos diferentes
- **WooCommerce Order API**: `WC_Order` com 12+ métodos utilizados
- **WordPress Cron**: Sistema de polling PIX a cada minuto
- **WooCommerce Logger**: `WC_Logger` em todos os gateways

### Moderadamente Acoplado ⚠️
- **WP REST API**: 4 endpoints públicos (sem proteção nonce)
- **WP Hooks/Filters**: 2 actions, 7 filtros customizados

### Baixo Acoplamento 👍
- **Banco de Dados**: Usa abstração WC (nunca SQL direto)
- **Sistema de Arquivos**: Apenas logs via WC

---

## 🔑 Descobertas Críticas

### 1. Comunicação HTTP
**TODAS as requisições usam funções nativas WordPress:**
- ✅ `wp_remote_post()` - Criar vendas, estornos
- ✅ `wp_remote_get()` - Consultar status
- ❌ **NUNCA** usa cURL, Guzzle ou file_get_contents

**Arquivos com HTTP:**
- `LknWcCieloRequest.php` (linhas 53, 233)
- `LknWCGatewayCieloCredit.php` (linha 834)
- `LknWCGatewayCieloDebit.php` (linhas 556, 1245, 1286, 1340, 1418)
- `LknWCGatewayCieloEndpoint.php` (linha 73)

### 2. Confirmação de Pagamento
**⚠️ NÃO usa webhooks da Cielo!**

Usa **polling via WP-Cron:**
- PIX: Verifica status a cada minuto
- Auto-limpeza após 2 horas
- Hook: `lkn_schedule_check_free_pix_payment_hook`

### 3. Manipulação de Pedidos
**SEMPRE usa métodos `WC_Order` - NUNCA funções procedurais:**
```php
$order->update_status()      // 2 ocorrências
$order->update_meta_data()   // 17+ metadados salvos
$order->add_order_note()     // 6 ocorrências
$order->set_transaction_id() // 3 ocorrências
```

### 4. Estornos
**Linha 1151 permite substituir TODA lógica:**
```php
$response = apply_filters('lkn_wc_cielo_credit_refund', 
    $url, $merchantId, $merchantSecret, $order_id, $amount
);
```
⚠️ **Risco de segurança**: Qualquer plugin pode estornar pagamentos.

### 5. Logging
**WC_Logger com mascaramento:**
- Credenciais: `abc123******ghi789`
- Cartões: `4111 **** **** 1111`
- CPF/CNPJ: `123********234`

Logs em 2 locais:
1. `wp-content/uploads/wc-logs/woocommerce-cielo-*.log`
2. Order meta: `lknWcCieloOrderLogs`

---

## 🧪 Estratégia de Testes

### Mocks Necessários (Brain\Monkey)

#### Funções WordPress (20+)
```php
wp_remote_post()
wp_remote_get()
is_wp_error()
wp_json_encode()
sanitize_text_field()
get_option()
wp_schedule_event()
wp_next_scheduled()
current_time()
do_action()
apply_filters()
```

#### Classes WooCommerce
```php
// Mockery
$orderMock = Mockery::mock('WC_Order');
$orderMock->shouldReceive('get_total')->andReturn(100.00);
$orderMock->shouldReceive('update_status')->with('processing');

$loggerMock = Mockery::mock('WC_Logger');
$loggerMock->shouldReceive('log')->with('error', ...);
```

---

## 📝 Checklist de Testes

### PIX (8 casos)
- [ ] Geração QR Code
- [ ] Agendamento cron
- [ ] Polling status (cada código Cielo)
- [ ] Auto-limpeza 2h
- [ ] Erro credenciais
- [ ] API offline
- [ ] Mascaramento CPF
- [ ] Metadata salvos

### Crédito (10 casos)
- [ ] Validação cartão
- [ ] Captura imediata
- [ ] Captura diferida
- [ ] Parcelas com juros
- [ ] Pagamento negado
- [ ] Erro rede
- [ ] Metadata salvos
- [ ] Mascaramento cartão
- [ ] Hook status customizado
- [ ] Zero Auth

### Estorno (5 casos)
- [ ] Estorno total
- [ ] Estorno parcial
- [ ] Transação cancelada
- [ ] Erro rede
- [ ] Filtro customizado

### Logging (5 casos)
- [ ] Debug ON salva
- [ ] Debug OFF não salva
- [ ] Credenciais mascaradas
- [ ] Order meta correto
- [ ] Metabox exibido

### Hooks (4 casos)
- [ ] Status customizado executado
- [ ] Filtro estorno pode substituir
- [ ] Zero Auth executado
- [ ] Suporte features adicionado

---

## ⚠️ Pontos de Atenção

### Segurança
1. **REST endpoints sem nonce**: `/checkCard`, `/getCardBrand` são públicos
2. **Filtros abertos**: Estorno pode ser hijacked por qualquer plugin
3. **Credenciais em exceções**: Se debug=off, podem vazar

### Performance
1. **Cron jobs acumulados**: 1000 pedidos PIX = 1000 crons/minuto
2. **Timeout 120s**: Pode bloquear PHP-FPM
3. **Polling agressivo**: Every minute pode ser muito

### Compatibilidade
- ✅ WooCommerce Blocks suportado
- ✅ HPOS (High-Performance Order Storage) suportado
- ⚠️ Subscriptions mencionado mas não implementado (free)

---

## 📂 Arquivos Mais Importantes

1. **LknWcCieloRequest.php** (257 linhas) - PIX + HTTP
2. **LknWCGatewayCieloCredit.php** (1.321 linhas) - Crédito
3. **LknWcCieloHelper.php** (710 linhas) - Helpers + Metadata
4. **LknWCGatewayCieloDebit.php** (1.746 linhas) - Débito + 3DS
5. **LknWCGatewayCieloEndpoint.php** (186 linhas) - REST API

---

## 🚀 Próximos Passos

1. **Revisar** documento com equipe QA
2. **Desenhar** casos de teste detalhados
3. **Implementar** mocks com Brain\Monkey
4. **Criar** fixtures de API Cielo (sandbox)
5. **Executar** testes e medir cobertura
6. **Ajustar** código baseado em resultados

---

## 🔗 Links Úteis

- **Documento Completo**: [MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md)
- **Brain\Monkey Docs**: https://giuseppe-mazzapica.gitbook.io/brain-monkey/
- **WooCommerce Unit Tests**: https://github.com/woocommerce/woocommerce/tree/trunk/tests
- **Cielo API Docs**: https://developercielo.github.io/manual/cielo-ecommerce

---

**Data do Mapeamento**: 2026-03-14  
**Versão do Plugin**: 1.29.0  
**Autor**: Análise automatizada para estratégia de testes unitários
