# WordPress Plugin Development Guidelines

## Architecture & SOLID Principles

**Single Responsibility Principle (SRP)**
- Each class has one reason to change - separate concerns (Admin, Public, Database, API calls)
- Use dedicated classes: `*Admin.php` for admin logic, `*Public.php` for frontend, `*Activator.php` for installation
- Functions should do one thing well - split large functions into smaller, focused ones

**Open/Closed Principle (OCP)**
- Extend functionality through hooks and filters, not by modifying existing code
- Use WordPress action/filter hooks: `add_action()`, `add_filter()`, `apply_filters()`, `do_action()`
- Create custom hooks for extensibility: `do_action('plugin_name_custom_hook', $data)`

**Liskov Substitution Principle (LSP)**
- Child classes must be substitutable for parent classes without breaking functionality
- Implement interfaces consistently across similar classes

**Interface Segregation Principle (ISP)**
- Create small, focused interfaces rather than large monolithic ones
- Separate admin interfaces from public interfaces

**Dependency Inversion Principle (DIP)**
- Depend on abstractions, not concrete implementations
- Use dependency injection where possible, especially for external services (APIs, databases)

## Development Methodology

- Test-Driven Development (TDD)-based development.

## Code Standards

**WordPress Coding Standards**
- Follow WordPress 6.8+ PHP 8.2+, JavaScript, CSS coding standards strictly
- Use WordPress nonce verification: `wp_verify_nonce()` for all form submissions
- Sanitize all inputs: `sanitize_text_field()`, `sanitize_email()`, etc.
- Escape all outputs: `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- Internationalize all user-facing strings: `__()`, `_e()`, `_n()`

**Naming Conventions**
- Use unique prefixes for all functions, classes, and globals to avoid conflicts
- Classes: `LknWCCielo*` (ex: `LknWCGatewayCieloCredit`, `LknWcCieloPix`)
- Functions: `lkn_wc_cielo_*`
- Hooks/filters: `lkn_wc_cielo_*`
- Constants: `LKN_WC_CIELO_*`
- Namespace: `Lkn\WCCieloPaymentGateway\Includes`

## Testing Requirements

**Unit Tests**
- Write PHPUnit tests for all business logic and utility functions
- Test WordPress hooks and filters behavior
- Mock external dependencies (WooCommerce, WordPress functions)
- Aim for 80%+ code coverage on critical paths

**Integration Tests**
- Test plugin activation/deactivation scenarios
- Test compatibility with WooCommerce updates
- Validate form submissions and data processing workflows

**Frontend Tests**
- Test JavaScript functionality with Jest or similar
- Validate React component behavior in WooCommerce Blocks
- Test responsive design and cross-browser compatibility

**Test Structure**

```php
// File: tests/unit/test-class-name.php
class Test_Class_Name extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        // Setup test data
    }
    
    public function test_specific_functionality() {
        // Arrange, Act, Assert pattern
    }
}
```

## Build Commands

```bash
# Install dependencies
composer install
npm install

# Build assets
npm run build

# Run all tests (use rtk prefix to compress output for Copilot context)
rtk test vendor/bin/phpunit

# Run specific test suite
rtk test vendor/bin/phpunit --testsuite pix
rtk test vendor/bin/phpunit --testsuite credit
rtk test vendor/bin/phpunit --testsuite refund

# Run with coverage
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --coverage-html coverage

# Code quality
vendor/bin/phan --no-progress-bar
```

## Project Architecture

Four payment gateways, each with a Blocks companion class:
- `LknWCGatewayCieloCredit` + `LknWcCieloCreditBlocks` — credit card
- `LknWCGatewayCieloDebit` + `LknWcCieloDebitBlocks` — debit card
- `LknWcCieloPix` + `LknWcCieloPixBlocks` — PIX
- `LknWCGatewayCieloGooglePay` + `LknWCGatewayCieloGooglePayBlocks` — Google Pay

Shared infrastructure:
- `LknWcCieloRequest` — all HTTP calls to Cielo API 3.0
- `LknWcCieloHelper` — utility methods (saveTransactionMetadata, icon URL, etc.)
- `LknWCCieloPaymentLoader` — hooks registration
- `LknWCGatewayCieloEndpoint` — REST endpoint for PIX status polling

Cielo API environments:
- Sandbox: `https://apisandbox.cieloecommerce.cielo.com.br/`
- Production: `https://api.cieloecommerce.cielo.com.br/`

Key decisions already made (do NOT revert):
- Partial capture uses `_lkn_cielo_capture_type` order meta, not global settings
- PIX CPF/CNPJ field priority: own field → `billing_cpf` → `billing_cnpj`
- `add_gateway_name_to_notes` strips `[$gateway_id]` prefix and prepends method title

## WordPress Plugin Specifics

**Hooks Priority**
- Use appropriate hook priorities to ensure correct execution order
- Document why specific priorities are chosen
- Test hook interactions with popular plugins

**Database Operations**
- Use `$wpdb` prepared statements for custom queries
- Leverage WordPress meta APIs when possible
- Create proper database cleanup routines in `uninstall.php`

**Asset Management**
- Use `wp_enqueue_script()` and `wp_enqueue_style()` properly
- Include asset versioning for cache busting
- Minimize HTTP requests with proper concatenation/minification

## Error Handling

- Use `WP_Error` for recoverable errors
- Log errors appropriately without exposing sensitive information
- Provide user-friendly error messages
- Implement graceful degradation for optional features

## Performance

- Cache expensive operations using WordPress transients
- Use lazy loading for admin-only functionality
- Optimize database queries - avoid N+1 problems
- Profile JavaScript performance in checkout flows

<!-- rtk-instructions v2 -->
# RTK — Token-Optimized CLI

**rtk** is a CLI proxy that filters and compresses command outputs, saving 60-90% tokens.

## Rule

Always prefix shell commands with `rtk`:

```bash
# Instead of:                                  Use:
git status                                     rtk git status
git log -10                                    rtk git log -10
git diff                                       rtk git diff
vendor/bin/phpunit                             rtk test vendor/bin/phpunit
vendor/bin/phpunit --testsuite pix             rtk test vendor/bin/phpunit --testsuite pix
grep -r "method" includes/                    rtk grep "method" includes/
find . -name "*.php" -not -path "*/vendor/*"  rtk find . -name "*.php" -not -path "*/vendor/*"
ls -la includes/                              rtk ls includes/
```

## Meta commands (use directly)

```bash
rtk gain              # Token savings dashboard
rtk gain --history    # Per-command savings history
rtk discover          # Find missed rtk opportunities
rtk proxy <cmd>       # Run raw (no filtering) but track usage
```
<!-- /rtk-instructions -->

<!-- cavemem-instructions v1 -->
# Cavemem — Persistent Cross-Session Memory

Cavemem is an MCP server that stores and retrieves observations across sessions.
The `mcp_cavemem_search` tool is available in this workspace.

## Rule

At the **start of any work session** on this plugin, call `mcp_cavemem_search` to recall relevant context before asking the user to re-explain:

```
mcp_cavemem_search("cielo payment gateway")       # general project context
mcp_cavemem_search("partial capture refund")      # refund/capture decisions
mcp_cavemem_search("pix cpf cnpj")               # PIX-specific decisions
mcp_cavemem_search("lkn_wc_cielo error")         # known bugs and fixes
```

## When to search
- Before starting any feature touching a gateway class
- Before writing or editing tests
- When the user mentions a bug or regression — check if it was seen before
- When approaching a complex hook interaction

## Note on Copilot + Cavemem
GitHub Copilot has built-in session memory (this file + `/memories/repo/`). Cavemem **extends** that with SQLite-backed persistence across IDE restarts and different sessions. Use both: repo memory for architecture decisions, Cavemem for session-level observations and error patterns.
<!-- /cavemem-instructions -->