<?php

namespace Lkn\WcCieloPaymentGateway\Exceptions;

use Exception;

/**
 * Base exception for payment-related errors
 *
 * @since 1.25.0
 */
class PaymentException extends Exception
{
    /**
     * Payment error code from API
     *
     * @var string|null
     */
    protected $paymentErrorCode;

    /**
     * Additional error data
     *
     * @var array
     */
    protected $errorData;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        ?string $paymentErrorCode = null,
        array $errorData = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->paymentErrorCode = $paymentErrorCode;
        $this->errorData = $errorData;
    }

    /**
     * Get payment error code
     *
     * @return string|null
     */
    public function getPaymentErrorCode(): ?string
    {
        return $this->paymentErrorCode;
    }

    /**
     * Get additional error data
     *
     * @return array
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
