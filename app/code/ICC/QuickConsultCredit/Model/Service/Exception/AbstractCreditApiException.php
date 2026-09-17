<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service\Exception;

use ICC\QuickConsultCredit\Api\Exception\CreditApiExceptionInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception as WebapiException;

/**
 * Base class for every Quick Consult Credit REST business-error exception.
 *
 * Extends WebapiException directly (rather than LocalizedException) so that the exact
 * HTTP status code required by the contract (see contracts/rest-api.md) is always used
 * as-is by Magento\Framework\Webapi\ErrorProcessor::maskException(), which passes
 * WebapiException instances through unchanged instead of re-mapping their HTTP code
 * from the exception's PHP type.
 */
abstract class AbstractCreditApiException extends WebapiException implements CreditApiExceptionInterface
{
    /**
     * @var string
     */
    private $errorCode;

    /**
     * @param Phrase $phrase
     * @param string $errorCode
     * @param int $httpCode
     */
    public function __construct(Phrase $phrase, string $errorCode, int $httpCode)
    {
        parent::__construct($phrase, 0, $httpCode);
        $this->errorCode = $errorCode;
    }

    /**
     * @inheritDoc
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
