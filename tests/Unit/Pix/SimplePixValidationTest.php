<?php

namespace Lkn\WCCieloPaymentGateway\Tests\Unit\Pix;

use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix;
use Lkn\WCCieloPaymentGateway\Tests\TestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Teste simplificado para validação de CPF/CNPJ
 *
 * @covers \Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix::validateCpfCnpj
 */
class SimplePixValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock apenas das constantes essenciais
        if (!defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.29.0');
        }

        // Mock mínimo das funções WordPress
        Functions\when('__')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('_x')->returnArg();
        Functions\when('apply_filters')->returnArg();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @test
     */
    public function testValidateCpfValid()
    {
        $gateway = new LknWcCieloPix();
        
        // CPF válido: 123.456.789-09
        $result = $gateway->validateCpfCnpj('12345678909');
        $this->assertTrue($result);
    }

    /**
     * @test 
     */
    public function testValidateCpfValidFormatted()
    {
        $gateway = new LknWcCieloPix();
        
        // CPF válido formatado: 123.456.789-09
        $result = $gateway->validateCpfCnpj('123.456.789-09');
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function testValidateCpfInvalidAllSameDigits()
    {
        $gateway = new LknWcCieloPix();
        
        // CPF inválido: todos os dígitos iguais
        $result = $gateway->validateCpfCnpj('11111111111');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateCpfInvalidChecksum()
    {
        $gateway = new LknWcCieloPix();
        
        // CPF inválido: dígito verificador errado
        $result = $gateway->validateCpfCnpj('12345678900');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateCnpjValid()
    {
        $gateway = new LknWcCieloPix();
        
        // CNPJ válido: 11.222.333/0001-81
        $result = $gateway->validateCpfCnpj('11222333000181');
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function testValidateCnpjValidFormatted()
    {
        $gateway = new LknWcCieloPix();
        
        // CNPJ válido formatado: 11.222.333/0001-81
        $result = $gateway->validateCpfCnpj('11.222.333/0001-81');
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function testValidateCnpjInvalidAllSameDigits()
    {
        $gateway = new LknWcCieloPix();
        
        // CNPJ inválido: todos os dígitos iguais
        $result = $gateway->validateCpfCnpj('11111111111111');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateCnpjInvalidChecksum()
    {
        $gateway = new LknWcCieloPix();
        
        // CNPJ inválido: dígito verificador errado
        $result = $gateway->validateCpfCnpj('11222333000100');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateDocumentTooShort()
    {
        $gateway = new LknWcCieloPix();
        
        // Documento muito curto
        $result = $gateway->validateCpfCnpj('123');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateDocumentTooLong()
    {
        $gateway = new LknWcCieloPix();
        
        // Documento muito longo
        $result = $gateway->validateCpfCnpj('123456789012345');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateEmptyDocument()
    {
        $gateway = new LknWcCieloPix();
        
        // Documento vazio
        $result = $gateway->validateCpfCnpj('');
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function testValidateDocumentWithLength12()
    {
        $gateway = new LknWcCieloPix();
        
        // Documento com 12 dígitos (nem CPF nem CNPJ)
        $result = $gateway->validateCpfCnpj('123456789012');
        $this->assertFalse($result);
    }
}