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
 * Thrown when an authenticated caller is not authorized for the requested customer or
 * action (customer-ownership violation on the balance endpoint, or a customer-session /
 * unaffiliated caller on the create-transaction endpoint) — HTTP 403 (QCC-API-009,
 * QCC-API-005, QCC-SEC-007).
 */
class UnauthorizedException extends AbstractCreditApiException
{
    public const ERROR_CODE = 'UNAUTHORIZED';

    /**
     * @param Phrase $phrase
     */
    public function __construct(Phrase $phrase)
    {
        parent::__construct($phrase, self::ERROR_CODE, WebapiException::HTTP_FORBIDDEN);
    }
}
