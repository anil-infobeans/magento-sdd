<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Api\Exception;

/**
 * Implemented by every Quick Consult Credit REST business-error exception so that the
 * standardized error payload (`{ "success": false, "error_code": "...", "message": "..." }`)
 * can be rendered for exactly these exceptions only, per QCC-API-012.
 *
 * @see \ICC\QuickConsultCredit\Model\Webapi\Rest\Response
 */
interface CreditApiExceptionInterface
{
    /**
     * Get the deterministic, machine-readable error code (e.g. INVALID_AMOUNT, CUSTOMER_NOT_FOUND).
     *
     * @return string
     */
    public function getErrorCode(): string;
}
