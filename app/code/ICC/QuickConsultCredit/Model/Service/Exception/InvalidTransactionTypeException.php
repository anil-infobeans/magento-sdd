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
 * Thrown when the create-transaction endpoint receives a `transaction_type` other than
 * `REDEEM` (QCC-API-017).
 */
class InvalidTransactionTypeException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'INVALID_TRANSACTION_TYPE';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_BAD_REQUEST);
    }
}
