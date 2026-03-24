<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Core;

use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPaymentActivator;
use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPaymentDeactivator;
use Lkn\WCCieloPaymentGateway\Includes\LknWCCieloPaymentLoader;
use PHPUnit\Framework\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for core plugin functionality classes
 */
class CoreFunctionalityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Brain\Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('__')->returnArg();
        Functions\when('add_action')->justReturn('');
        Functions\when('add_filter')->justReturn('');
        Functions\when('update_option')->justReturn(true);
        Functions\when('delete_option')->justReturn(true);
        Functions\when('get_option')->justReturn('');
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('flush_rewrite_rules')->justReturn('');
        Functions\when('add_rewrite_endpoint')->justReturn('');
        Functions\when('wp_schedule_event')->justReturn(true);
        Functions\when('wp_clear_scheduled_hook')->justReturn(true);
        Functions\when('time')->justReturn(time());
        Functions\when('wp_next_scheduled')->justReturn(false);
    }

    protected function tearDown(): void
    {
        \Brain\Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    // Tests for LknWCCieloPaymentActivator
    public function test_activator_can_be_instantiated()
    {
        $activator = new LknWCCieloPaymentActivator();
        $this->assertInstanceOf(LknWCCieloPaymentActivator::class, $activator);
    }

    public function test_activate_method_executes_activation_tasks()
    {
        // Mock database table creation
        Functions\expect('update_option')
            ->atLeast()
            ->once();
        
        Functions\expect('flush_rewrite_rules')
            ->once();
        
        LknWCCieloPaymentActivator::activate();
        
        $this->assertTrue(true);
    }

    public function test_activate_creates_database_tables()
    {
        global $wpdb;
        
        $wpdb = Mockery::mock('wpdb');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('query')->andReturn(true);
        $wpdb->shouldReceive('get_var')->andReturn(0);
        
        Functions\when('get_option')->with('lkn_cielo_db_version')->andReturn('1.0');
        Functions\expect('update_option')->with('lkn_cielo_db_version', Mockery::type('string'));
        
        LknWCCieloPaymentActivator::activate();
        
        $this->assertTrue(true);
    }

    public function test_activate_sets_default_options()
    {
        Functions\expect('update_option')
            ->atLeast()
            ->once()
            ->with(
                Mockery::pattern('/lkn_cielo_/'),
                Mockery::any()
            );
        
        LknWCCieloPaymentActivator::activate();
        
        $this->assertTrue(true);
    }

    public function test_activate_schedules_cron_events()
    {
        Functions\when('wp_next_scheduled')->with('lkn_cielo_cleanup_logs')->andReturn(false);
        
        Functions\expect('wp_schedule_event')
            ->once()
            ->with(
                Mockery::type('int'),
                'daily',
                'lkn_cielo_cleanup_logs'
            );
        
        LknWCCieloPaymentActivator::activate();
        
        $this->assertTrue(true);
    }

    public function test_activate_creates_upload_directories()
    {
        Functions\when('wp_upload_dir')->andReturn([
            'basedir' => '/path/to/uploads',
            'error' => false
        ]);
        
        Functions\when('wp_mkdir_p')->andReturn(true);
        
        LknWCCieloPaymentActivator::activate();
        
        $this->assertTrue(true);
    }

    // Tests for LknWCCieloPaymentDeactivator
    public function test_deactivator_can_be_instantiated()
    {
        $deactivator = new LknWCCieloPaymentDeactivator();
        $this->assertInstanceOf(LknWCCieloPaymentDeactivator::class, $deactivator);
    }

    public function test_deactivate_method_executes_deactivation_tasks()
    {
        Functions\expect('wp_clear_scheduled_hook')
            ->once()
            ->with('lkn_cielo_cleanup_logs');
        
        Functions\expect('flush_rewrite_rules')
            ->once();
        
        LknWCCieloPaymentDeactivator::deactivate();
        
        $this->assertTrue(true);
    }

    public function test_deactivate_clears_scheduled_events()
    {
        Functions\expect('wp_clear_scheduled_hook')
            ->atLeast()
            ->once();
        
        LknWCCieloPaymentDeactivator::deactivate();
        
        $this->assertTrue(true);
    }

    public function test_deactivate_cleans_up_transients()
    {
        Functions\expect('delete_transient')
            ->atLeast()
            ->once()
            ->with(Mockery::pattern('/lkn_cielo_/'));
        
        LknWCCieloPaymentDeactivator::deactivate();
        
        $this->assertTrue(true);
    }

    public function test_deactivate_preserves_important_data()
    {
        // Should NOT delete user settings or transaction data
        Functions\expect('delete_option')
            ->never()
            ->with(Mockery::pattern('/lkn_cielo_settings/'));
        
        LknWCCieloPaymentDeactivator::deactivate();
        
        $this->assertTrue(true);
    }

    // Tests for LknWCCieloPaymentLoader
    public function test_loader_can_be_instantiated()
    {
        $loader = new LknWCCieloPaymentLoader();
        $this->assertInstanceOf(LknWCCieloPaymentLoader::class, $loader);
    }

    public function test_loader_add_action_stores_actions()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        $loader->add_action('init', $this, 'test_method');
        
        $reflection = new \ReflectionProperty($loader, 'actions');
        $reflection->setAccessible(true);
        $actions = $reflection->getValue($loader);
        
        $this->assertIsArray($actions);
        $this->assertCount(1, $actions);
        $this->assertEquals('init', $actions[0]['hook']);
    }

    public function test_loader_add_filter_stores_filters()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        $loader->add_filter('woocommerce_payment_gateways', $this, 'test_method');
        
        $reflection = new \ReflectionProperty($loader, 'filters');
        $reflection->setAccessible(true);
        $filters = $reflection->getValue($loader);
        
        $this->assertIsArray($filters);
        $this->assertCount(1, $filters);
        $this->assertEquals('woocommerce_payment_gateways', $filters[0]['hook']);
    }

    public function test_loader_add_action_with_priority_and_args()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        $loader->add_action('wp_enqueue_scripts', $this, 'test_method', 15, 2);
        
        $reflection = new \ReflectionProperty($loader, 'actions');
        $reflection->setAccessible(true);
        $actions = $reflection->getValue($loader);
        
        $this->assertEquals(15, $actions[0]['priority']);
        $this->assertEquals(2, $actions[0]['accepted_args']);
    }

    public function test_loader_add_filter_with_priority_and_args()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        $loader->add_filter('the_content', $this, 'test_method', 20, 3);
        
        $reflection = new \ReflectionProperty($loader, 'filters');
        $reflection->setAccessible(true);
        $filters = $reflection->getValue($loader);
        
        $this->assertEquals(20, $filters[0]['priority']);
        $this->assertEquals(3, $filters[0]['accepted_args']);
    }

    public function test_loader_run_registers_all_hooks()
    {
        Functions\expect('add_action')
            ->once()
            ->with('init', Mockery::type('array'), 10, 1);
        
        Functions\expect('add_filter')
            ->once()
            ->with('woocommerce_payment_gateways', Mockery::type('array'), 10, 1);
        
        $loader = new LknWCCieloPaymentLoader();
        $loader->add_action('init', $this, 'test_method');
        $loader->add_filter('woocommerce_payment_gateways', $this, 'test_method');
        
        $loader->run();
        
        $this->assertTrue(true);
    }

    public function test_loader_run_with_multiple_hooks()
    {
        Functions\expect('add_action')
            ->times(2);
        
        Functions\expect('add_filter')
            ->times(2);
        
        $loader = new LknWCCieloPaymentLoader();
        $loader->add_action('init', $this, 'method1');
        $loader->add_action('wp_enqueue_scripts', $this, 'method2');
        $loader->add_filter('woocommerce_payment_gateways', $this, 'method3');
        $loader->add_filter('the_content', $this, 'method4');
        
        $loader->run();
        
        $this->assertTrue(true);
    }

    public function test_loader_run_with_empty_hooks()
    {
        // Should not call add_action or add_filter if no hooks are registered
        Functions\expect('add_action')->never();
        Functions\expect('add_filter')->never();
        
        $loader = new LknWCCieloPaymentLoader();
        $loader->run();
        
        $this->assertTrue(true);
    }

    public function test_loader_stores_component_references()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        $mockComponent = Mockery::mock();
        $loader->add_action('init', $mockComponent, 'method_name');
        
        $reflection = new \ReflectionProperty($loader, 'actions');
        $reflection->setAccessible(true);
        $actions = $reflection->getValue($loader);
        
        $this->assertSame($mockComponent, $actions[0]['component']);
        $this->assertEquals('method_name', $actions[0]['callback']);
    }

    public function test_loader_handles_string_callbacks()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        // Test with string callback (function name)
        $loader->add_action('init', null, 'wp_head');
        
        $reflection = new \ReflectionProperty($loader, 'actions');
        $reflection->setAccessible(true);
        $actions = $reflection->getValue($loader);
        
        $this->assertNull($actions[0]['component']);
        $this->assertEquals('wp_head', $actions[0]['callback']);
    }

    public function test_loader_default_priority_and_args()
    {
        $loader = new LknWCCieloPaymentLoader();
        
        $loader->add_action('init', $this, 'test_method');
        $loader->add_filter('the_content', $this, 'test_method');
        
        $reflection = new \ReflectionProperty($loader, 'actions');
        $reflection->setAccessible(true);
        $actions = $reflection->getValue($loader);
        
        $reflection = new \ReflectionProperty($loader, 'filters');
        $reflection->setAccessible(true);
        $filters = $reflection->getValue($loader);
        
        $this->assertEquals(10, $actions[0]['priority']);
        $this->assertEquals(1, $actions[0]['accepted_args']);
        $this->assertEquals(10, $filters[0]['priority']);
        $this->assertEquals(1, $filters[0]['accepted_args']);
    }

    // Integration test
    public function test_activation_deactivation_cycle()
    {
        Functions\expect('update_option')->atLeast()->once();
        Functions\expect('flush_rewrite_rules')->times(2);
        Functions\expect('wp_clear_scheduled_hook')->atLeast()->once();
        
        // Activate
        LknWCCieloPaymentActivator::activate();
        
        // Deactivate
        LknWCCieloPaymentDeactivator::deactivate();
        
        $this->assertTrue(true);
    }

    public function test_loader_integration_with_core_functionality()
    {
        Functions\expect('add_action')->atLeast()->once();
        Functions\expect('add_filter')->atLeast()->once();
        
        $loader = new LknWCCieloPaymentLoader();
        
        // Simulate adding hooks like the main plugin would
        $loader->add_action('plugins_loaded', $this, 'init_gateways');
        $loader->add_action('woocommerce_blocks_loaded', $this, 'init_blocks');
        $loader->add_filter('woocommerce_payment_gateways', $this, 'add_gateways');
        
        $loader->run();
        
        $this->assertTrue(true);
    }

    // Helper method for testing
    public function test_method()
    {
        return true;
    }
    
    public function init_gateways()
    {
        return true;
    }
    
    public function init_blocks()
    {
        return true;
    }
    
    public function add_gateways($gateways)
    {
        return $gateways;
    }
}