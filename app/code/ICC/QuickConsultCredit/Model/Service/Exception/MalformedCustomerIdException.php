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
 * Thrown when the `customerId` path parameter on the balance endpoint is malformed
 * (non-numeric, zero, negative, or out of range) — rejected as HTTP 401 with
 * `error_code = CUSTOMER_NOT_FOUND`, distinct from the 404 well-formed-but-nonexistent
 * case (QCC-API-018).
 */
class MalformedCustomerIdException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'CUSTOMER_NOT_FOUND';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_UNAUTHORIZED);
    }
}
