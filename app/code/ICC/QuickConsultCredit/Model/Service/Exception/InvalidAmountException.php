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
 * Thrown when a structurally valid integer `amount` is <= 0 (QCC-API-006, QCC-REDEEM-004/005).
 */
class InvalidAmountException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'INVALID_AMOUNT';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_BAD_REQUEST);
    }
}
