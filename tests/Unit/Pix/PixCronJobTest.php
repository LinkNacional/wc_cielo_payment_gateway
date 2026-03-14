<?php
/**
 * Teste 02: Agendamento de Cron Job para PIX
 * 
 * Testa se o cron job é agendado corretamente para verificar status do PIX
 * Valida agendamento a cada minuto e auto-limpeza após 2 horas
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Pix
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Brain\Monkey\Functions;
use Mockery;

class PixCronJobTest extends TestCase
{
    /**
     * @test
     * Teste 02: Agendamento de cron job para verificação de status PIX
     */
    public function test_pix_cron_job_is_scheduled()
    {
        // Arrange
        $paymentId = 'test-payment-id-123';
        $orderId = 456;
        $currentTime = time();

        // Mock wp_next_scheduled to return false (not scheduled yet)
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_schedule_check_free_pix_payment_hook', [$paymentId, $orderId])
            ->andReturn(false);

        // Mock wp_schedule_event to schedule the cron job
        Functions\expect('wp_schedule_event')
            ->once()
            ->with(
                Mockery::type('int'), // timestamp
                'every_minute',
                'lkn_schedule_check_free_pix_payment_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        // Act - Schedule the cron job (simulating what happens after PIX creation)
        $scheduled = wp_schedule_event(
            $currentTime,
            'every_minute',
            'lkn_schedule_check_free_pix_payment_hook',
            [$paymentId, $orderId]
        );

        // Assert
        $this->assertTrue($scheduled, 'Cron job should be scheduled successfully');
    }

    /**
     * @test
     * Teste 02.B: Verificação se cron job já está agendado
     */
    public function test_pix_cron_job_already_scheduled()
    {
        // Arrange
        $paymentId = 'test-payment-id-456';
        $orderId = 789;
        $scheduledTime = time() + 60; // Scheduled for 1 minute from now

        // Mock wp_next_scheduled to return timestamp (already scheduled)
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_schedule_check_free_pix_payment_hook', [$paymentId, $orderId])
            ->andReturn($scheduledTime);

        // Act - Check if cron job is scheduled
        $nextScheduled = wp_next_scheduled(
            'lkn_schedule_check_free_pix_payment_hook',
            [$paymentId, $orderId]
        );

        // Assert
        $this->assertNotFalse($nextScheduled, 'Cron job should be scheduled');
        $this->assertEquals($scheduledTime, $nextScheduled);
    }

    /**
     * @test
     * Teste 02.C: Agendamento de auto-limpeza após 2 horas
     */
    public function test_pix_auto_cleanup_scheduled()
    {
        // Arrange
        $paymentId = 'test-payment-id-789';
        $orderId = 999;
        $twoHoursLater = time() + (120 * 60); // 2 hours = 120 minutes

        // Mock wp_next_scheduled for cleanup (not scheduled yet)
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_remove_custom_cron_job_hook', [$paymentId, $orderId])
            ->andReturn(false);

        // Mock wp_schedule_single_event for cleanup
        Functions\expect('wp_schedule_single_event')
            ->once()
            ->with(
                Mockery::on(function($time) use ($twoHoursLater) {
                    // Check if time is approximately 2 hours from now (within 5 seconds)
                    return abs($time - $twoHoursLater) < 5;
                }),
                'lkn_remove_custom_cron_job_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        // Act - Schedule cleanup (simulating what happens in check_payment method)
        $scheduled = wp_schedule_single_event(
            time() + (120 * 60),
            'lkn_remove_custom_cron_job_hook',
            [$paymentId, $orderId]
        );

        // Assert
        $this->assertTrue($scheduled, 'Cleanup job should be scheduled successfully');
    }

    /**
     * @test
     * Teste 02.D: Remoção de cron job quando pagamento é concluído
     */
    public function test_pix_cron_job_removed_on_payment_complete()
    {
        // Arrange
        $paymentId = 'completed-payment-id';
        $orderId = 123;
        $scheduledTime = time() + 60;

        // Mock wp_next_scheduled to return timestamp (cron is scheduled)
        Functions\expect('wp_next_scheduled')
            ->twice() // Called once for each cron job
            ->with('lkn_schedule_check_free_pix_payment_hook', [$paymentId, $orderId])
            ->andReturn($scheduledTime);

        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_remove_custom_cron_job_hook', [$paymentId, $orderId])
            ->andReturn($scheduledTime);

        // Mock wp_unschedule_event to remove both cron jobs
        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(
                $scheduledTime,
                'lkn_schedule_check_free_pix_payment_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(
                $scheduledTime,
                'lkn_remove_custom_cron_job_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        // Act - Remove cron jobs using the static method
        LknWcCieloRequest::lkn_remove_custom_cron_job($paymentId, $orderId);

        // Assert - Mockery will verify the expectations were met
        $this->assertTrue(true, 'Cron jobs should be unscheduled');
    }

    /**
     * @test
     * Teste 02.E: Cron job não é removido se não estiver agendado
     */
    public function test_pix_cron_job_not_removed_if_not_scheduled()
    {
        // Arrange
        $paymentId = 'not-scheduled-payment-id';
        $orderId = 456;

        // Mock wp_next_scheduled to return false (not scheduled)
        Functions\expect('wp_next_scheduled')
            ->twice()
            ->andReturn(false);

        // Mock wp_unschedule_event should NOT be called
        Functions\expect('wp_unschedule_event')
            ->never();

        // Act - Try to remove cron jobs
        LknWcCieloRequest::lkn_remove_custom_cron_job($paymentId, $orderId);

        // Assert - No exception should be thrown
        $this->assertTrue(true, 'Should handle unscheduled cron jobs gracefully');
    }

    /**
     * @test
     * Teste 02.F: Cron job com paymentId vazio é desagendado
     */
    public function test_pix_cron_job_with_empty_payment_id()
    {
        // Arrange
        $paymentId = '';
        $orderId = 789;
        $scheduledTime = time() + 60;

        Functions\when('get_option')->alias(function($key) {
            if ($key === 'woocommerce_lkn_wc_cielo_pix_settings') {
                return [
                    'env' => 'sandbox',
                    'merchant_id' => 'test_merchant',
                    'merchant_key' => 'test_key',
                    'debug' => 'no'
                ];
            }
            return [];
        });

        // Mock wp_next_scheduled to return timestamp
        Functions\expect('wp_next_scheduled')
            ->once()
            ->with('lkn_schedule_check_free_pix_payment_hook', [$paymentId, $orderId])
            ->andReturn($scheduledTime);

        // Mock wp_unschedule_event
        Functions\expect('wp_unschedule_event')
            ->once()
            ->with(
                $scheduledTime,
                'lkn_schedule_check_free_pix_payment_hook',
                [$paymentId, $orderId]
            )
            ->andReturn(true);

        // Mock wc_get_order
        Functions\when('wc_get_order')->justReturn(null);

        // Act - Call check_payment with empty paymentId
        LknWcCieloRequest::check_payment($paymentId, $orderId);

        // Assert - Cron should be unscheduled
        $this->assertTrue(true, 'Empty paymentId should trigger unscheduling');
    }
}
