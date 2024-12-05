# Forma de pagamento Cielo API 3.0 para WooCommerce

Forma de pagamento [Cielo API 3.0 para WooCommerce](https://www.linknacional.com.br/wordpress/woocommerce/cielo/), cartão de crédito e débito.

## Perguntas frequentes

- O plugin é gratuito?
Sim, [clique aqui](https://wordpress.org/plugins/lkn-wc-gateway-cielo/) para ir para página de download oficial.

- Do que preciso para usar este plugin?
* Precisa ter instalado o plugin WooCommerce;
* É necessário ter credenciais Cielo API 3.0 para utilizar o plugin.

## Método de instalação

1) Procure na barra lateral a área de plugins do WordPress;

2) Nos plugins instalados procure a opção 'adicionar novo';

3) Clique na opção 'enviar plugin' no título da página e faça o upload do plugin lkn-wc-gateway-cielo.zip;

4) Clique no botão 'instalar agora' e então ative o plugin instalado;

5) Agora vá para o menu de configurações do WooCommerce;

6) Selecione a opção 'Pagamentos' e procure por 'Cartão de crédito Cielo / Cartão de débito Cielo';

7) Insira todas as credenciais necessárias para cada gateway de pagamento;

8) Clique em salvar.

## Configurações de ambiente de testes

1) Primeiro acesse as configurações do WooCommerce;

2) Pesquise e clique em 'Pagamentos';

3) Dentro desta opção, procure o título do plugin 'Cartão de crédito Cielo / Cartão de débito Cielo' e clique nele;

4) Insira todas as credenciais exigidas pelo plugin;

5) Para configurar o ambiente de teste em 'Ambiente', escolha a opção 'Teste' e habilite 'Depuração';

6) Salve as configurações.

Nota: As credenciais para ambientes de produção e teste são diferentes, ao alternar entre ambientes lembre-se de alterar as credenciais da API.

## Hooks

# lkn_wc_cielo_set_installment_limit

| Tipo            | Descrição                               | Parametros                                                 |
|-----------------|-----------------------------------------|------------------------------------------------------------|
| `apply_filters` | Define o número limite de parcelamento. | `string $limit = '12', AbstractPaymentMethodType $gateway` |

> **$limit**: Por padrão, o limite é definido como 12, permitindo que o responsável pelo gerenciamento do plugin defina um limite de parcelamento customizado.

> **$gateway**: Informações sobre o pagamento, como `merchant_id`, `merchant_key`, tipo de pagamento e outros dados necessários para o processo de validação. Essas informações são obtidas pela instanciação da classe `LknWCGatewayCieloCredit()` ou `LknWCGatewayCieloDebit()` na função: `initialize()`.

> **Exemplo**:
```php
    add_filter('lkn_wc_cielo_set_installment_limit', function($limit, $gateway) {
        $limit = 20;

        return $limit;
    })

    apply_filters('lkn_wc_cielo_set_installment_limit', $limit, $gateway);
```

# lkn_wc_cielo_set_installments

| Tipo            | Descrição                               | Parametros                                                     |
|-----------------|-----------------------------------------|----------------------------------------------------------------|
| `apply_filters` | Define o nome da label nas opções.      | `Array $installments = [], AbstractPaymentMethodType $gateway` |


> **$installments**: Array contendo a lista de opções disponíveis no momento do pagamento.

> **$gateway**: Informações sobre o pagamento, como `merchant_id`, `merchant_key`, tipo de pagamento e outros dados necessários para o processo de validação. Essas informações são obtidas pela instanciação da classe `LknWCGatewayCieloCredit()` ou `LknWCGatewayCieloDebit()` na função: `initialize()`.

> **Exemplo**:
```php
add_filter('lkn_wc_cielo_set_installments', function() {
    $installments[] = array('id' => '1', 'label' => $index . 'x de ' . $fomartedNumber);

    return $installments;
})

apply_filters('lkn_wc_cielo_set_installments', $installments, $gateway);
```
> **OBS**: Caso o usuário não defina nenhum valor, o resultado será um array vazio.


