<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Plugin\Webapi;

use ICC\QuickConsultCredit\Model\Service\AuthorizationAuditLogger;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Webapi\Authorization;
use Magento\Framework\Webapi\Rest\Request;

/**
 * Scoped exclusively to the two Quick Consult Credit REST routes:
 *
 * 1. Escalates the "self" ACL check on the balance endpoint so that an admin/integration
 *    caller holding the `ICC_QuickConsultCredit::credit` resource can also reach it, since
 *    Magento's declarative webapi ACL has no native OR semantics between the "self"
 *    resource (satisfiable only by a customer token) and a named ACL resource.
 * 2. Records the structured authorization-decision audit log entry required by
 *    QCC-API-011.
 *
 * For every other route/resource in the system this plugin is a no-op passthrough — it
 * never alters behavior outside these two paths (Constitution Principle XII).
 */
class AuthorizationPlugin
{
    private const BALANCE_PATH_PREFIX = '/V1/quick-consult-credit/balance/';
    private const TRANSACTIONS_PATH_PREFIX = '/V1/quick-consult-credit/transactions';
    private const RESOURCE_CREDIT = 'ICC_QuickConsultCredit::credit';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthorizationAuditLogger
     */
    private $auditLogger;

    /**
     * @param Request $request
     * @param AuthorizationInterface $authorization
     * @param UserContextInterface $userContext
     * @param AuthorizationAuditLogger $auditLogger
     */
    public function __construct(
        Request $request,
        AuthorizationInterface $authorization,
        UserContextInterface $userContext,
        AuthorizationAuditLogger $auditLogger
    ) {
        $this->request = $request;
        $this->authorization = $authorization;
        $this->userContext = $userContext;
        $this->auditLogger = $auditLogger;
    }

    /**
     * @param Authorization $subject
     * @param callable $proceed
     * @param string[] $aclResources
     * @return bool
     */
    public function aroundIsAllowed(Authorization $subject, callable $proceed, $aclResources): bool
    {
        $path = $this->currentPath();
        if (!$this->isQccRoute($path)) {
            return $proceed($aclResources);
        }

        $allowed = (bool) $proceed($aclResources);

        if (!$allowed && $aclResources === ['self'] && $this->startsWith($path, self::BALANCE_PATH_PREFIX)) {
            $allowed = (bool) $this->authorization->isAllowed(self::RESOURCE_CREDIT);
        }

        $this->auditLogger->log(
            $this->resolveCategory(),
            $allowed ? AuthorizationAuditLogger::DECISION_GRANTED : AuthorizationAuditLogger::DECISION_DENIED
        );

        return $allowed;
    }

    /**
     * @return string
     */
    private function currentPath(): string
    {
        try {
            return (string) $this->request->getPathInfo();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    private function isQccRoute(string $path): bool
    {
        return $this->startsWith($path, self::BALANCE_PATH_PREFIX)
            || $this->startsWith($path, self::TRANSACTIONS_PATH_PREFIX);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * @return string
     */
    private function resolveCategory(): string
    {
        try {
            $userType = $this->userContext->getUserType();
        } catch (\Throwable $e) {
            return AuthorizationAuditLogger::CATEGORY_AUTHENTICATION;
        }

        switch ($userType) {
            case UserContextInterface::USER_TYPE_ADMIN:
                return AuthorizationAuditLogger::CATEGORY_ADMIN_ACL;
            case UserContextInterface::USER_TYPE_INTEGRATION:
                return AuthorizationAuditLogger::CATEGORY_INTEGRATION_ACL;
            case UserContextInterface::USER_TYPE_CUSTOMER:
                return AuthorizationAuditLogger::CATEGORY_CUSTOMER_OWNERSHIP;
            default:
                return AuthorizationAuditLogger::CATEGORY_AUTHENTICATION;
        }
    }
}
