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
 * Thrown when a well-formed `customerId`/`customer_id` does not correspond to an existing
 * customer (QCC-API-003, QCC-API-008, QCC-API-018 AC-2).
 */
class CustomerNotFoundException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'CUSTOMER_NOT_FOUND';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_NOT_FOUND);
    }
}
