# 1.16.0 - 31/01/2025
* Adição do notice de donwloado do plugin: fraud-detection-for-woocommerce.
* Adição de mensagem de avaliação do plugin no footer.

# 1.15.0 - 20/12/2024
* Adição de função para renovar token;
* Adição de configuração para exibir logs de transação no pedido;
* Adição de cartão animado no checkout do editor por blocos;
* Adição de configuração para exibir e ocultar cartão animado;
* Adição de seleção automática das abas quando a página configurações é recarregada.
* Alteração de label do método de pagamento de crédito;
* Alteração em urls de endpoints.

# 1.14.1 - 06/12/2024
* Correção de bug para a função de mostrar logs no pedido.

# 1.14.0 - 05/12/2024
* Adição de compatibilidade com botão para capturar pedido;
* Adição de configurações para adicionar logs no pedido;
* Correção em script de layout.

# 1.13.2 - 02/12/2024
* Correção de hook de limitação de parcelamento;
* Correção de evento de consulta bin formulário legado.

# 1.13.1 - 29/11/2024
* Correção de formatação de valor do pedido.

# 1.13.0 - 25/11/2024
* Adição de configuração para permitir transações sem 3DS;
* Adição de filtros para alterar URL de consultas de cartão;
* Alteração em layout de configurações;
* Correção em chamada de de logger.

# 1.12.1 - 06/11/2024
* Adição de correções para transações autenticadas com 3DS 2.2

# 1.12.0 - 22/10/2024
* Adição de compatibilidade com novas funcionalidades do WooCommerce Cielo PRO.

# 1.11.5 - 10/10/2024
* Correção em validação de nonce;
* Alteração em placeholders.

# 1.11.4 - 04/10/2024
* Adicionado modo de compatibilidade para validação de nonce;
* Corrigido erro no envio de cartão de crédito inválido.

# 1.11.3 - 02/10/2024
* Adição de melhoria na validação de campos;
* Ajustes no redirecionamento em caso de falha no pagamento;
* Correção de pedido com parcelamento com juros tem valor total diferente do produto.

# 1.11.2 - 27/09/2024
* Correção de placeholder para WooCommerce classic checkout;
* Correção de cálculo de valores de parcelamento para clientes com Cielo PRO ativo.

# 1.11.1 - 25/09/2024
* Correção em valores das parcelas quando são feitas alterações no carrinho.

# 1.11.0 - 02/09/2024
* Adição de traduções para campo de CPF para pagamentos com Pix.
* Adição compatibilidade com configuração para alterar status quando o pedido for pago.

# 1.10.0 - 16/08/2024
* Adição de pagamentos de crédito com validação 3DS;
* Adição de validação de BIN para selecionar tipo de cartão automáticamente;
* Correção de bug de renderização débito 3DS;
* Melhorias de compatibilidade com WooCommerce Cielo PRO;
* Adição de dependência com o plugin WooCommerce.

# 1.9.3 - 18/06/2024
* Adição de compatibilidade com a funcionalidade Orders Auto-Complete do plugin pro;
* Correção de problema de renderização do Cielo débito 3DS.

# 1.9.2 - 05/06/2024
* Correção de validação no campo nome do titular do cartão;

# 1.9.1 - 23/05/2024
* Correção de erro no campo de titular do cartão;
* Correção de erro na validação dos campos do novo template de checkout;
* Ajustes no reconhecimento de parcelas em faturas e páginas fora do padrão do WooCommerce.

# 1.9.0 - 22/05/2024
* Correção de erro nos campos de entrada do formulário;
* Adição de tratamento de erro para o campo de entrada do titular do cartão;
* Adição de campo de entrada do titular do cartão;
* Adição de botão "seja pro".

# 1.8.1 - 16/05/2024
* Correção de erro na página de editar formulário em blocos;

# 1.8.0 - 10/05/2024
* Adição de compatibilidade com block-based checkout;
* Adição de banner de hospedagem para Wordpress;
* Adição de campos obrigatórios para as credenciais de cartão de crédito e débito;
* Melhoria nos tratamentos de erros;

# 1.7.0 - 25/03/2024
* Correção de bug de carregamento de script do 3DS na opção cartão de débito;
* Adição de lógica para lidar com respostas da API quando o código de retorno é "GF";
* Ajuste na label de código de segurança;
* Adição de descrição nas configurações do cartão de débito;
* Adição de validação no campo de descrição da fatura;

# 1.6.0 - 27/11/2023
* Correção de bug de carregamento de script do 3DS;
* Adição de exibição de informações adicionais do cartão nos detalhes do pedido;
* Correção de bug de máscaras dos campos de cartão de débito;
* Remoção de funções depreciadas do JQuery;
* Adição de botão de 'ver logs' nas configurações do plugin.

# 1.5.0
* Adição de regras de lintagem;
* Adição de carregamento de atributos globais ao script de parcelamento;
* Atualização de repositório para suportar devcontainers.

# 1.4.0
* Implementação de modo de compatibilidade de validação;
* Ajustes nas descrições de configurações;
* Atualizar links de notices;
* Armazenamento de quantidade de parcelas nos metadados do pedido;
* Adicionado quantidade de parcelas na tela de obrigado e no e-mail de novo pedido.

# 1.3.2
* Correção de mensagens de validação duplicadas;
* Ajustes de carregamento de configurações;
* Mudança de diretório de scripts da área administrativa.

# 1.3.1
* Otimização de carregamento de script;
* Removido carregamento de script desnecessário.

# 1.3.0
* Mudança de biblioteca de máscara de campos;
* Implementado preparação para reconhecimento de juros;
* Implementada área de avisos no front-end.

# 1.2.0
* Implementado filtro de BIN;
* Corrigido bugs de inputs na página de finalizar pagamento;
* Carregamento de scripts otimizado;
* Corrigido bug de pagamento de cartão de débito na página de finalizar pagamento.

# 1.1.0
* Implementação de parcelamento;
* Atualização de documentação;
* Correção de bug de validação de data de expiração;
* Correção de bug de inicialização de 3DS.

# 1.0.0
* Lançamento de plugin.