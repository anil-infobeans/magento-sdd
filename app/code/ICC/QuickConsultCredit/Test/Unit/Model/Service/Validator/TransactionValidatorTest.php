<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\Service\Validator;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use ICC\QuickConsultCredit\Model\Service\Exception\InsufficientBalanceException;
use ICC\QuickConsultCredit\Model\Service\Exception\InvalidAmountException;
use ICC\QuickConsultCredit\Model\Service\Validator\TransactionValidator;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

/**
 * Covers QCC-REDEEM-004/005, QCC-LEDGER-004, QCC-DATA-001, and CLA-001/CLA-002
 * (whole-number-only amount handling).
 */
class TransactionValidatorTest extends TestCase
{
    /**
     * @var TransactionValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new TransactionValidator();
    }

    public function testZeroAmountIsRejected(): void
    {
        $this->expectException(InvalidAmountException::class);
        $this->validator->validateAmount(0);
    }

    public function testNegativeAmountIsRejected(): void
    {
        $this->expectException(InvalidAmountException::class);
        $this->validator->validateAmount(-5);
    }

    public function testPositiveAmountIsAccepted(): void
    {
        $this->validator->validateAmount(25);
        $this->addToAssertionCount(1);
    }

    public function testMissingReasonIsRejectedForAdminAdd(): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validateMessage(CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD, null);
    }

    public function testMissingReasonIsRejectedForAdminRemove(): void
    {
        $this->expectException(LocalizedException::class);
        $this->validator->validateMessage(CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE, '   ');
    }

    public function testReasonNotRequiredForRedeem(): void
    {
        $this->validator->validateMessage(CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, null);
        $this->addToAssertionCount(1);
    }

    public function testDebitExceedingBalanceIsRejected(): void
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->validator->validateSufficientBalance(10, 25);
    }

    public function testDebitEqualToBalanceIsAccepted(): void
    {
        $this->validator->validateSufficientBalance(25, 25);
        $this->addToAssertionCount(1);
    }

    /**
     * CLA-001/CLA-002: amounts are whole-number credit points. PHP's `int` type hint on
     * validateAmount() already rejects fractional input at the type-system level; this
     * test documents that a fractional value can only reach the validator after having
     * already been rejected upstream (QCC-API-019 INVALID_REQUEST at the REST boundary).
     */
    public function testFractionalAmountCannotBePassedDueToIntTypeHint(): void
    {
        $reflection = new \ReflectionMethod(TransactionValidator::class, 'validateAmount');
        $parameterType = $reflection->getParameters()[0]->getType();

        self::assertNotNull($parameterType);
        self::assertSame('int', (string) $parameterType);
    }

    /**
     * CLA-001/CLA-002: because the class declares strict_types=1, passing a fractional
     * float (e.g. 25.5) directly to validateAmount() raises a \TypeError at the PHP
     * engine level rather than being silently truncated/coerced to an integer -
     * proving fractional amounts are structurally impossible to process, not merely
     * discouraged by convention. The value is produced via json_decode() so static
     * analysis cannot infer it as a float literal at the call site.
     */
    public function testFractionalAmountRaisesTypeErrorRatherThanBeingCoerced(): void
    {
        $this->expectException(\TypeError::class);

        /** @var mixed $fractionalAmount */
        $fractionalAmount = json_decode('25.5');
        $this->validator->validateAmount($fractionalAmount);
    }
}
