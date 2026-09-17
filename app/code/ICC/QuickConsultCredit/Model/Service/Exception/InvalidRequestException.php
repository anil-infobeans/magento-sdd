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
 * Thrown when a create-transaction request is missing a required field or supplies a
 * non-integer/fractional `amount` (QCC-API-019).
 */
class InvalidRequestException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'INVALID_REQUEST';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_BAD_REQUEST);
    }
}
