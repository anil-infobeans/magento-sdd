<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model;

use ICC\QuickConsultCredit\Api\Data\CreditTransactionResultInterface;

/**
 * @inheritDoc
 */
class CreditTransactionResult implements CreditTransactionResultInterface
{
    /**
     * @var int
     */
    private $transactionId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var int
     */
    private $previousBalance;

    /**
     * @var int
     */
    private $currentBalance;

    /**
     * @param int $transactionId
     * @param string $type
     * @param int $amount
     * @param int $previousBalance
     * @param int $currentBalance
     */
    public function __construct(
        int $transactionId,
        string $type,
        int $amount,
        int $previousBalance,
        int $currentBalance
    ) {
        $this->transactionId = $transactionId;
        $this->type = $type;
        $this->amount = $amount;
        $this->previousBalance = $previousBalance;
        $this->currentBalance = $currentBalance;
    }

    /**
     * @inheritDoc
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @inheritDoc
     */
    public function getPreviousBalance(): int
    {
        return $this->previousBalance;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentBalance(): int
    {
        return $this->currentBalance;
    }
}
