<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service\Exception;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception as WebapiException;

/**
 * Thrown when a debit amount exceeds the customer's current balance (QCC-API-007, QCC-REDEEM-006).
 */
class InsufficientBalanceException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'INSUFFICIENT_BALANCE';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_BAD_REQUEST);
    }
}
