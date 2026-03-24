<?php
/**
 * Teste 09: Validação de Número de Cartão de Crédito
 * 
 * Testa a validação do número do cartão de crédito
 * Valida formato, comprimento mínimo e caracteres válidos
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Credit
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Credit;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Brain\Monkey\Functions;
use Mockery;

class CreditCardValidationTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WooCommerce functions for gateway initialization
        Functions\when('wc_get_logger')->justReturn($this->createMockLogger());
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        
        $this->gateway = new LknWCGatewayCieloCredit();
    }

    /**
     * @test
     * Teste 09.A: Número de cartão válido (16 dígitos)
     */
    public function test_valid_card_number()
    {
        // Arrange
        $validCardNumber = '4111111111111111'; // Visa test card
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_card_number');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->gateway, $validCardNumber, false);

        // Assert
        $this->assertTrue($result, 'Valid 16-digit card number should pass validation');
    }

    /**
     * @test
     * Teste 09.B: Número de cartão vazio (erro)
     */
    public function test_empty_card_number()
    {
        // Arrange
        $emptyCardNumber = '';
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_card_number');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->gateway, $emptyCardNumber, false);

        // Assert
        $this->assertFalse($result, 'Empty card number should fail validation');
    }

    /**
     * @test
     * Teste 09.C: Número de cartão com caracteres inválidos (erro)
     */
    public function test_card_number_with_invalid_characters()
    {
        // Arrange
        $invalidCardNumbers = [
            '4111-1111-1111-1111', // With dashes
            '4111a111b111c111',    // With letters
            '4111!111@111#111',    // With special chars
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_card_number');
        $method->setAccessible(true);

        foreach ($invalidCardNumbers as $cardNumber) {
            // Act
            $result = $method->invoke($this->gateway, $cardNumber, false);

            // Assert
            $this->assertFalse(
                $result,
                "Card number with invalid characters should fail validation: {$cardNumber}"
            );
        }
    }

    /**
     * @test
     * Teste 09.D: Número de cartão muito curto (<12 dígitos)
     */
    public function test_card_number_too_short()
    {
        // Arrange
        $shortCardNumbers = [
            '411111',      // 6 digits
            '41111111',    // 8 digits
            '4111111111',  // 10 digits
            '41111111111', // 11 digits - less than minimum 12
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_card_number');
        $method->setAccessible(true);

        foreach ($shortCardNumbers as $cardNumber) {
            // Act
            $result = $method->invoke($this->gateway, $cardNumber, false);

            // Assert
            $this->assertFalse(
                $result,
                "Card number with less than 12 digits should fail: {$cardNumber} (length: " . strlen($cardNumber) . ")"
            );
        }
    }

    /**
     * @test
     * Teste 09.E: Números de cartão válidos de diferentes comprimentos
     */
    public function test_valid_card_numbers_various_lengths()
    {
        // Arrange - Valid card numbers from 12 to 19 digits
        $validCardNumbers = [
            '123456789012',    // 12 digits (minimum)
            '1234567890123',   // 13 digits
            '12345678901234',  // 14 digits
            '123456789012345', // 15 digits (Amex)
            '4111111111111111', // 16 digits (Visa/Master)
            '41111111111111111', // 17 digits
            '411111111111111111', // 18 digits
            '4111111111111111111', // 19 digits (maximum)
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_card_number');
        $method->setAccessible(true);

        foreach ($validCardNumbers as $cardNumber) {
            // Act
            $result = $method->invoke($this->gateway, $cardNumber, false);

            // Assert
            $this->assertTrue(
                $result,
                "Valid card number should pass validation: {$cardNumber} (length: " . strlen($cardNumber) . ")"
            );
        }
    }

    /**
     * @test
     * Teste 10: Validação de data de validade
     */
    public function test_valid_expiration_date()
    {
        // Arrange - Valid future date
        $futureDate = date('m/y', strtotime('+2 years'));
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_exp_date');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->gateway, $futureDate, false);

        // Assert
        $this->assertTrue($result, 'Valid future expiration date should pass validation');
    }

    /**
     * @test
     * Teste 10.B: Data de validade vazia (erro)
     */
    public function test_empty_expiration_date()
    {
        // Arrange
        $emptyDate = '';
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_exp_date');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->gateway, $emptyDate, false);

        // Assert
        $this->assertFalse($result, 'Empty expiration date should fail validation');
    }

    /**
     * @test
     * Teste 10.C: Data de validade expirada (erro)
     */
    public function test_expired_date()
    {
        // Arrange - Past date
        $pastDate = date('m/y', strtotime('-2 years'));
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_exp_date');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->gateway, $pastDate, false);

        // Assert
        $this->assertFalse($result, 'Expired date should fail validation');
    }

    /**
     * @test
     * Teste 10.D: Formato inválido de data (erro)
     */
    public function test_invalid_expiration_date_format()
    {
        // Arrange
        $invalidDates = [
            '13/25',      // Invalid month
            '00/25',      // Invalid month
            'ab/cd',      // Letters
            '12-25',      // Wrong separator
            '12/2025',    // Wrong year format (should be 2-digit)
            '1/25',       // Single digit month (may or may not be accepted)
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_exp_date');
        $method->setAccessible(true);

        foreach ($invalidDates as $date) {
            // Act
            $result = $method->invoke($this->gateway, $date, false);

            // Assert
            $this->assertFalse(
                $result,
                "Invalid date format should fail validation: {$date}"
            );
        }
    }

    /**
     * @test
     * Teste 11: Validação de CVV
     */
    public function test_valid_cvv()
    {
        // Arrange
        $validCvvs = [
            '123',  // 3 digits (Visa, Mastercard)
            '1234', // 4 digits (Amex)
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_cvv');
        $method->setAccessible(true);

        foreach ($validCvvs as $cvv) {
            // Act
            $result = $method->invoke($this->gateway, $cvv, false);

            // Assert
            $this->assertTrue(
                $result,
                "Valid CVV should pass validation: {$cvv}"
            );
        }
    }

    /**
     * @test
     * Teste 11.B: CVV vazio (erro)
     */
    public function test_empty_cvv()
    {
        // Arrange
        $emptyCvv = '';
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_cvv');
        $method->setAccessible(true);

        // Act
        $result = $method->invoke($this->gateway, $emptyCvv, false);

        // Assert
        $this->assertFalse($result, 'Empty CVV should fail validation');
    }

    /**
     * @test
     * Teste 11.C: CVV com caracteres inválidos (erro)
     */
    public function test_cvv_with_invalid_characters()
    {
        // Arrange
        $invalidCvvs = [
            'abc',   // Letters
            '12a',   // Mixed
            '12!',   // Special chars
            '1 2 3', // Spaces
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_cvv');
        $method->setAccessible(true);

        foreach ($invalidCvvs as $cvv) {
            // Act
            $result = $method->invoke($this->gateway, $cvv, false);

            // Assert
            $this->assertFalse(
                $result,
                "CVV with invalid characters should fail validation: {$cvv}"
            );
        }
    }

    /**
     * @test
     * Teste 11.D: CVV muito curto (<3 dígitos)
     */
    public function test_cvv_too_short()
    {
        // Arrange
        $shortCvvs = [
            '1',   // 1 digit
            '12',  // 2 digits
        ];
        
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('validate_cvv');
        $method->setAccessible(true);

        foreach ($shortCvvs as $cvv) {
            // Act
            $result = $method->invoke($this->gateway, $cvv, false);

            // Assert
            $this->assertFalse(
                $result,
                "CVV with less than 3 digits should fail validation: {$cvv}"
            );
        }
    }
}
