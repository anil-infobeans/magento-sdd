<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Service;

use Psr\Log\LoggerInterface;

/**
 * Records a structured, externally verifiable operational log entry for every
 * authorization decision on the Quick Consult Credit REST endpoints (QCC-API-011).
 */
class AuthorizationAuditLogger
{
    public const CATEGORY_AUTHENTICATION = 'AUTHENTICATION';
    public const CATEGORY_CUSTOMER_OWNERSHIP = 'CUSTOMER_OWNERSHIP';
    public const CATEGORY_INTEGRATION_ACL = 'INTEGRATION_ACL';
    public const CATEGORY_ADMIN_ACL = 'ADMIN_ACL';

    public const DECISION_GRANTED = 'GRANTED';
    public const DECISION_DENIED = 'DENIED';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log an authorization decision. Never includes credentials/tokens (QCC-SEC-005).
     *
     * @param string $category One of the CATEGORY_* constants.
     * @param string $decision One of the DECISION_* constants.
     * @param array $context Additional non-sensitive context (e.g. customer_id).
     * @return void
     */
    public function log(string $category, string $decision, array $context = []): void
    {
        $this->logger->info(
            'qcc_authorization_decision',
            array_merge(
                [
                    'authorization_category' => $category,
                    'decision' => $decision,
                ],
                $context
            )
        );
    }

    /**
     * Log a distinct business-validation or authorization rejection for the
     * create-transaction endpoint (QCC-AUDIT-007), separate from the operational
     * failure log entries in {@see CreditTransactionManagement::execute()} and
     * {@see CreditPurchaseProcessor::postPurchase()}.
     *
     * @param string $errorCode One of the standardized REST `error_code` values.
     * @param array $context Additional non-sensitive context (e.g. customer_id).
     * @return void
     */
    public function logRejection(string $errorCode, array $context = []): void
    {
        $this->logger->info(
            'qcc_request_rejected',
            array_merge(['error_code' => $errorCode], $context)
        );
    }

    /**
     * Log a distinct "accepted" audit entry for every independently-validated
     * create-transaction request that succeeds (QCC-AUDIT-007). Since no
     * request-identity/deduplication mechanism exists for this endpoint (QCC-API-013,
     * CLA-004 resolved: no dedicated idempotency mechanism), a genuine resubmission is
     * indistinguishable from an original request at this layer; every accepted request
     * is therefore logged uniformly, which necessarily includes every accepted
     * resubmission.
     *
     * @param array $context Additional non-sensitive context (e.g. customer_id, transaction_id).
     * @return void
     */
    public function logResubmissionAccepted(array $context = []): void
    {
        $this->logger->info('qcc_request_accepted', $context);
    }
}
