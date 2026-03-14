<?php
/**
 * Teste 07: Mascaramento de CPF/CNPJ em PIX
 * 
 * Testa se dados sensíveis (CPF/CNPJ) são mascarados corretamente
 * antes de serem enviados para a API ou salvos em logs
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests\Unit\Pix
 */

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloRequest;
use Brain\Monkey\Functions;
use Mockery;

class PixDataMaskingTest extends TestCase
{
    /**
     * @test
     * Teste 07.A: Mascaramento de CPF (11 dígitos)
     */
    public function test_cpf_is_masked()
    {
        // Arrange
        $originalCpf = '12345678900';
        $expectedMaskedPattern = '/^\d{1}.*\*{8}.*\d{2}$/'; // Starts with digits, has 8 asterisks, ends with 2 digits

        // Use reflection to test private maskSensitiveData method
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        // Act
        $maskedCpf = $method->invoke($pixRequest, $originalCpf);

        // Assert
        $this->assertNotEquals($originalCpf, $maskedCpf, 'CPF should be masked');
        $this->assertStringContainsString('*', $maskedCpf, 'Masked CPF should contain asterisks');
        $this->assertMatchesRegularExpression($expectedMaskedPattern, $maskedCpf, 'CPF should follow masking pattern');
        
        // Verify original digits are not all visible
        $this->assertStringNotContainsString('345678', $maskedCpf, 'Middle digits should be masked');
    }

    /**
     * @test
     * Teste 07.B: Mascaramento de CNPJ (14 dígitos)
     */
    public function test_cnpj_is_masked()
    {
        // Arrange
        $originalCnpj = '12345678901234';
        
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        // Act
        $maskedCnpj = $method->invoke($pixRequest, $originalCnpj);

        // Assert
        $this->assertNotEquals($originalCnpj, $maskedCnpj, 'CNPJ should be masked');
        $this->assertStringContainsString('*', $maskedCnpj, 'Masked CNPJ should contain asterisks');
        $this->assertEquals(14, strlen($maskedCnpj), 'Masked CNPJ should maintain original length');
        
        // Verify middle digits are masked
        $this->assertStringNotContainsString('34567890', $maskedCnpj, 'Middle digits should be masked');
    }

    /**
     * @test
     * Teste 07.C: Mascaramento de Merchant ID
     */
    public function test_merchant_id_is_masked()
    {
        // Arrange
        $originalMerchantId = '1234567890abcdef';
        
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        // Act
        $maskedMerchantId = $method->invoke($pixRequest, $originalMerchantId);

        // Assert
        $this->assertNotEquals($originalMerchantId, $maskedMerchantId, 'Merchant ID should be masked');
        $this->assertStringContainsString('*', $maskedMerchantId, 'Masked Merchant ID should contain asterisks');
        $this->assertStringDoesNotContainSensitiveData($maskedMerchantId, '567890ab');
    }

    /**
     * @test
     * Teste 07.D: Mascaramento de Merchant Key
     */
    public function test_merchant_key_is_masked()
    {
        // Arrange
        $originalMerchantKey = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456';
        
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        // Act
        $maskedMerchantKey = $method->invoke($pixRequest, $originalMerchantKey);

        // Assert
        $this->assertNotEquals($originalMerchantKey, $maskedMerchantKey, 'Merchant Key should be masked');
        $this->assertStringContainsString('*', $maskedMerchantKey, 'Masked Merchant Key should contain asterisks');
        $this->assertStringDoesNotContainSensitiveData($maskedMerchantKey, 'HIJKLMNOPQRSTUV');
    }

    /**
     * @test
     * Teste 07.E: Mascaramento preserva comprimento mínimo
     */
    public function test_masking_preserves_minimum_length()
    {
        // Arrange - Test with different length strings
        $testStrings = [
            '123456789012',     // 12 chars
            '1234567890123',    // 13 chars
            '12345678901234',   // 14 chars
            '123456789012345',  // 15 chars
        ];
        
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        foreach ($testStrings as $testString) {
            // Act
            $masked = $method->invoke($pixRequest, $testString);

            // Assert - All should contain exactly 8 asterisks
            $asteriskCount = substr_count($masked, '*');
            $this->assertEquals(8, $asteriskCount, "String of length {$testString} should have 8 asterisks");
            $this->assertEquals(strlen($testString), strlen($masked), "Masked string should have same length as original");
        }
    }

    /**
     * @test
     * Teste 07.F: Mascaramento com string vazia ou nula
     */
    public function test_masking_with_empty_string()
    {
        // Arrange
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        // Act & Assert - Empty string
        $maskedEmpty = $method->invoke($pixRequest, '');
        $this->assertEquals('********', $maskedEmpty, 'Empty string should return 8 asterisks');
        
        // Act & Assert - Very short string (less than 8 chars)
        $shortString = '1234';
        $maskedShort = $method->invoke($pixRequest, $shortString);
        $this->assertStringContainsString('*', $maskedShort, 'Short string should still be masked');
    }

    /**
     * @test
     * Teste 07.G: CPF/CNPJ mascarado não pode ser usado para reconstruir original
     */
    public function test_masked_data_cannot_be_reconstructed()
    {
        // Arrange
        $originalCpf = '12345678900';
        $originalCnpj = '12345678901234';
        
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        // Act
        $maskedCpf = $method->invoke($pixRequest, $originalCpf);
        $maskedCnpj = $method->invoke($pixRequest, $originalCnpj);

        // Assert - Masked data should not contain enough info to reconstruct
        // At least 8 consecutive characters should be masked
        $this->assertGreaterThanOrEqual(8, substr_count($maskedCpf, '*'));
        $this->assertGreaterThanOrEqual(8, substr_count($maskedCnpj, '*'));
        
        // No 4+ consecutive original digits should be visible
        $cpfSubstrings = ['2345', '3456', '4567', '5678', '6789', '7890'];
        foreach ($cpfSubstrings as $substring) {
            $this->assertStringNotContainsString($substring, $maskedCpf, "CPF should not contain substring: {$substring}");
        }
    }

    /**
     * @test
     * Teste 07.H: Dados mascarados são diferentes mas reconhecíveis
     */
    public function test_masked_data_is_recognizable_but_secure()
    {
        // Arrange
        $testData = [
            'cpf1' => '11111111111',
            'cpf2' => '22222222222',
            'cnpj1' => '11111111111111',
            'cnpj2' => '22222222222222',
        ];
        
        $pixRequest = new LknWcCieloRequest();
        $reflection = new \ReflectionClass($pixRequest);
        $method = $reflection->getMethod('maskSensitiveData');
        $method->setAccessible(true);

        $masked = [];
        foreach ($testData as $key => $value) {
            $masked[$key] = $method->invoke($pixRequest, $value);
        }

        // Assert - Different inputs produce different masked outputs
        $this->assertNotEquals($masked['cpf1'], $masked['cpf2'], 'Different CPFs should produce different masked values');
        $this->assertNotEquals($masked['cnpj1'], $masked['cnpj2'], 'Different CNPJs should produce different masked values');
        
        // But all contain masking
        foreach ($masked as $maskedValue) {
            $this->assertStringContainsString('*', $maskedValue);
        }
    }
}
