# Diretrizes para o GitHub Copilot - Plugin Pix (Baseado no WordPress Plugin Boilerplate)

Este documento define a arquitetura e as convenções para o desenvolvimento do nosso plugin de gateway de pagamento Pix para WooCommerce.

**FUNDAMENTO:** O projeto é baseado no WordPress Plugin Boilerplate. A estrutura de arquivos (admin, public, includes), o método de registro de hooks (através da classe Loader) e os padrões de codificação do boilerplate DEVEM ser seguidos para todo o código de integração com o WordPress.

## 1. Arquitetura Híbrida

O plugin é dividido em duas áreas lógicas principais:

### 1. Camada de Integração WordPress (Estrutura Boilerplate):
   - Responsável por toda a comunicação com a API do WordPress (hooks, filtros, páginas de admin, enfileiramento de scripts).
   - Utiliza os diretórios admin/, public/ e includes/.
   - Segue os padrões de nomenclatura do WordPress: `Nome_Da_Classe` e `nome_da_funcao()`.

### 2. Camada de Lógica de Negócio (PSR-4):
   - Contém a lógica central do nosso sistema de pagamento: as implementações das APIs (Gateways), os contratos (Interfaces) e os serviços (HttpClient, etc.).
   - Esta camada é o mais desacoplada possível do WordPress.
   - Reside no diretório src/ e usa o autoloader do Composer (PSR-4).
   - Segue os padrões de nomenclatura PSR-4: `NomeDaClasse` e `metodoEmCamelCase()`.

## 2. Estrutura de Diretórios Adaptada
```
/plugin-name/
├── admin/                          (Lógica do painel WP. Usa os serviços de `src/`)
│   ├── class-plugin-name-admin.php
│   ├── js/
│   └── partials/
│
├── includes/                       (Orquestrador principal do plugin)
│   ├── class-plugin-name.php      (Orquestrador. Instancia o Service Container)
│   ├── class-plugin-name-loader.php (Registrador de Actions e Filters)
│   └── ...                        (outros arquivos do boilerplate)
│
├── public/                         (Lógica do front-end. Usa os serviços de `src/`)
│   ├── class-plugin-name-public.php
│   └── ...
│
├── src/                           (NOSSA LÓGICA DE NEGÓCIO - PSR-4)
│   ├── Contracts/
│   │   └── PixApiInterface.php
│   │
│   ├── Gateways/
│   │   ├── AbstractApiGateway.php
│   │   └── C6Bank/
│   │       └── C6BankGateway.php
│   │
│   └── Services/
│       ├── ServiceContainer.php
│       ├── SettingsManager.php
│       ├── WebhookRouter.php
│       └── HttpClient.php
│
├── plugin-name.php                (Arquivo de boot do plugin)
└── composer.json                  (Para o autoloader PSR-4 do diretório `src/`)
```

## 3. Regras de Implementação Estritas
### 3.1. O Registrador de Hooks (Loader) é Mandatório

**REGRA DE OURO:** Nenhuma chamada `add_action()` ou `add_filter()` deve ser feita diretamente. TODOS os hooks devem ser registrados na classe `includes/class-plugin-name.php` através do serviço `$this->loader`.

- **ERRADO:** 
  ```php
  add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
  ```

- **CORRETO** (dentro de `includes/class-plugin-name.php`):
  ```php
  $plugin_admin = new Plugin_Name_Admin(
      $this->get_plugin_name(), 
      $this->get_version() 
  );
  $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_page' );
  ```
### 3.2. Lógica de Negócio vs. Integração WordPress

- **A lógica de "O QUÊ" fazer fica em `src/`:**
  - Exemplo: A classe `C6BankGateway` sabe como gerar uma cobrança Pix na API do C6. Ela não sabe o que é um "hook" do WordPress.

- **A lógica de "QUANDO" fazer fica nos diretórios do boilerplate (admin, public, includes):**
  - Exemplo: A classe `class-plugin-name-admin.php` registra um hook para salvar as opções do plugin. Quando acionado, ele chama um método do serviço `SettingsManager` (de `src/`) para efetivamente salvar os dados.
### 3.3. Adicionando um Novo Gateway de Pagamento

O processo permanece o mesmo da arquitetura original, pois ocorre inteiramente dentro de `src/`:

1. Crie um novo subdiretório em `src/Gateways/`. Ex: `src/Gateways/Itau/`.
2. Crie a classe `ItauGateway.php` que implementa `PixApiInterface`.
3. Registre o novo gateway no `ServiceContainer` (que é instanciado em `includes/class-plugin-name.php`).
4. Adicione as opções do novo gateway no `SettingsManager`. A classe `class-plugin-name-admin.php` irá usar o `SettingsManager` para exibir esses campos.
### 3.4. Injeção de Dependência e o Service Container

- O `ServiceContainer` DEVE ser instanciado no construtor da classe principal `Plugin_Name` (em `includes/class-plugin-name.php`).
- As classes do boilerplate (`Plugin_Name_Admin`, `Plugin_Name_Public`) podem receber o `ServiceContainer` ou serviços específicos em seus construtores para poderem utilizá-los.
- As classes dentro de `src/` NUNCA devem usar `new` para instanciar outros serviços. Elas recebem suas dependências via construtor.
### 3.5. Tratamento de Erros e Webhooks

As regras sobre Exceções Específicas e o Roteador de Webhook Inteligente permanecem as mesmas, pois são parte da lógica de negócio em `src/`. O hook do WordPress para a API de webhooks (`add_action('woocommerce_api_my_gateway', ...)`) será registrado no Loader e seu callback irá invocar o nosso `WebhookRouter`.
## 4. Exemplo de Fluxo de Trabalho Mental

**Tarefa:** "Criar a página de configurações do plugin no admin do WordPress."

**Copilot, pense assim:**

1. "Isso é uma funcionalidade de admin. A lógica de registro de hooks pertence a `includes/class-plugin-name.php`."

2. "A classe que implementará a lógica da página de admin é a `admin/class-plugin-name-admin.php`."

3. "Preciso de um hook de menu. Em `includes/class-plugin-name.php`, dentro do método `define_admin_hooks`, vou adicionar:
   ```php
   $this->loader->add_action('admin_menu', $plugin_admin, 'add_options_page');
   ```"

4. "Agora, em `admin/class-plugin-name-admin.php`, vou criar o método `add_options_page()`."

5. "Dentro de `add_options_page()`, preciso dos campos de configuração. Quem sabe disso? O serviço `SettingsManager` de `src/Services/`. Vou garantir que a classe `Plugin_Name_Admin` receba essa dependência."

6. "O `SettingsManager` me dará a estrutura dos campos. Vou usar as funções do WordPress (`add_options_page`, `register_setting`, etc.) para renderizar o formulário, possivelmente usando um partial de `admin/partials/`."

7. "O JavaScript para mostrar/ocultar os campos de cada gateway será enfileirado no método `enqueue_scripts()` da `Plugin_Name_Admin`, e o hook para isso (`admin_enqueue_scripts`) será registrado no Loader."