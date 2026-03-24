# 🗺️ Mapeamento de Acoplamento - WooCommerce Cielo Payment Gateway

> Documentação técnica completa para estratégia de testes unitários com PHPUnit e Brain\Monkey

**Versão do Plugin:** 1.29.0  
**Data do Mapeamento:** 2026-03-14  
**Total de Documentação:** 2.724 linhas em 4 documentos

---

## 📚 Acesso Rápido aos Documentos

### 👉 **COMECE AQUI:** [INDICE_DOCUMENTACAO.md](./INDICE_DOCUMENTACAO.md)
Índice completo com guias de leitura por perfil e navegação estruturada.

---

### 📄 Documentos Principais

| Documento | Descrição | Linhas | Tempo | Público |
|-----------|-----------|--------|-------|---------|
| **[RESUMO_EXECUTIVO.md](./RESUMO_EXECUTIVO.md)** | Visão geral de 1 página com descobertas críticas | 212 | 5 min | Gerentes, Tech Leads |
| **[MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md)** | Análise técnica completa com 14 seções | 1.487 | 45 min | Desenvolvedores, Arquitetos |
| **[GUIA_VISUAL_REFERENCIA.md](./GUIA_VISUAL_REFERENCIA.md)** | Diagramas, fluxogramas e exemplos de código | 759 | 20 min | Devs implementando testes |
| **[INDICE_DOCUMENTACAO.md](./INDICE_DOCUMENTACAO.md)** | Índice navegável com guias de leitura | 266 | 3 min | Todos os perfis |

---

## 🎯 Por Onde Começar?

### Se você tem 5 minutos:
→ Leia [RESUMO_EXECUTIVO.md](./RESUMO_EXECUTIVO.md)

### Se você tem 30 minutos:
1. [RESUMO_EXECUTIVO.md](./RESUMO_EXECUTIVO.md) (5 min)
2. [GUIA_VISUAL_REFERENCIA.md](./GUIA_VISUAL_REFERENCIA.md) - Seções 1-4 (20 min)
3. Seção "Conclusão" do [MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md) (5 min)

### Se você vai implementar testes:
1. [INDICE_DOCUMENTACAO.md](./INDICE_DOCUMENTACAO.md) - Contexto (3 min)
2. [GUIA_VISUAL_REFERENCIA.md](./GUIA_VISUAL_REFERENCIA.md) - Todos os diagramas (20 min)
3. [MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md) - Leitura completa (45 min)

---

## 🔍 O Que Você Vai Encontrar

### ✅ Comunicação HTTP
- **10 pontos** mapeados com linhas exatas de código
- 100% usa `wp_remote_post()` e `wp_remote_get()` (WordPress native)
- NUNCA usa cURL, Guzzle ou file_get_contents

### ✅ Manipulação de Pedidos
- **12+ métodos** `WC_Order` documentados
- **17 metadados** salvos por transação
- NUNCA usa `update_post_meta()` direto

### ✅ Sistema de Confirmação
- **Polling via WP-Cron** (NÃO webhooks!)
- Verifica status PIX a cada minuto
- Auto-limpeza após 2 horas

### ✅ Logging e Exceções
- **WC_Logger** com 4 níveis
- **3 métodos** de mascaramento de dados sensíveis
- Logs em arquivos + order meta

### ✅ Fluxos Críticos Mapeados
- **PIX:** 11 passos sequenciados
- **Crédito:** 12 passos com validações
- **Estorno:** 10 passos com filtros

### ✅ Hooks e Filtros
- **2 actions** customizadas
- **7 filtros** (incluindo refund hijackável ⚠️)

### ✅ Checklist de Testes
- **32 casos de teste** organizados
- **30+ funções** WordPress/WooCommerce a mockar
- Exemplos de código com Brain\Monkey + Mockery

### ⚠️ Pontos de Atenção
- **4 REST endpoints** públicos sem nonce
- **Filtro de estorno** substituível por qualquer plugin
- **Polling agressivo** pode impactar performance

---

## 📊 Estatísticas do Mapeamento

```
📁 Arquivos PHP analisados:    22
📝 Linhas de código:            ~8.000
🌐 Pontos HTTP:                 10
🛒 Métodos WC_Order:            12+
🔌 Hooks/Filtros:               9
🔗 REST endpoints:              4
💾 Metadados por pedido:        17
✅ Casos de teste:              32
📚 Linhas de documentação:      2.724
📄 Documentos criados:          4
```

---

## 🧪 Para Implementar Testes

### 1. Instalar Dependências
```bash
composer require --dev phpunit/phpunit
composer require --dev brain/monkey
composer require --dev mockery/mockery
```

### 2. Ver Exemplos Completos
→ Consulte a seção "6. Mapeamento de Mocks" em [GUIA_VISUAL_REFERENCIA.md](./GUIA_VISUAL_REFERENCIA.md)

### 3. Seguir Checklist
→ 32 casos de teste em [MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md) seção 11

---

## 🎓 Conclusão

**Nível de Acoplamento: MÉDIO-ALTO**

✅ **Pontos Fortes:**
- Código limpo e bem estruturado
- Usa apenas abstrações nativas (testável)
- Mascaramento automático de dados sensíveis

⚠️ **Pontos de Melhoria:**
- REST endpoints precisam proteção
- Sistema de polling pode impactar performance
- Filtros de estorno muito abertos

📖 **Ver análise completa:** [MAPEAMENTO_TECNICO_ACOPLAMENTO.md](./MAPEAMENTO_TECNICO_ACOPLAMENTO.md) seção 14

---

## 🔗 Links Úteis

- **Brain\Monkey Docs:** https://giuseppe-mazzapica.gitbook.io/brain-monkey/
- **Mockery Docs:** http://docs.mockery.io/
- **WooCommerce Tests:** https://github.com/woocommerce/woocommerce/tree/trunk/tests
- **Cielo API Docs:** https://developercielo.github.io/manual/cielo-ecommerce

---

## 📝 Próximos Passos

1. ✅ Revisar documentação com equipe
2. ⏳ Corrigir vulnerabilidades de segurança
3. ⏳ Implementar testes unitários (32 casos)
4. ⏳ Criar fixtures de API Cielo
5. ⏳ Medir cobertura de código
6. ⏳ Refatorar baseado em resultados

---

**🎉 Documentação completa e pronta para uso!**

**Começar agora →** [INDICE_DOCUMENTACAO.md](./INDICE_DOCUMENTACAO.md)
