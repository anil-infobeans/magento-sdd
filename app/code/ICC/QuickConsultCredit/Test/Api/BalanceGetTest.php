<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Api;

use Magento\Framework\Webapi\Exception as HttpExceptionCodes;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Bootstrap as TestBootstrap;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * API contract test for `GET /V1/quick-consult-credit/balance/:customerId`
 * (QCC-API-001/002/003/004/009/018).
 */
class BalanceGetTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/quick-consult-credit/balance';
    private const SERVICE_NAME = 'iccQuickConsultCreditCreditBalanceManagementV1';
    private const SERVICE_VERSION = 'V1';

    /**
     * @var CustomerTokenServiceInterface
     */
    private $tokenService;

    /**
     * @var AdminTokenServiceInterface
     */
    private $adminTokenService;

    protected function setUp(): void
    {
        $this->tokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $this->adminTokenService = Bootstrap::getObjectManager()->get(AdminTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testAuthenticatedCustomerReceivesZeroBalanceForOwnAccountWithNoLedgerRows(): void
    {
        $this->_markTestAsRestOnly();
        $token = $this->tokenService->createCustomerAccessToken('customer@example.com', 'password');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/1',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];

        // QCC-API-004: a customer with no account row yet receives a transient
        // zero-balance response rather than a 404.
        $response = $this->_webApiCall($serviceInfo);
        self::assertSame(0, $response['balance']);
        self::assertSame(1, $response['customer_id']);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/two_customers.php
     */
    public function testCrossCustomerAccessIsRejected(): void
    {
        $this->_markTestAsRestOnly();
        $token = $this->tokenService->createCustomerAccessToken('customer@example.com', 'password');

        $serviceInfo = [
            'rest' => [
                // Customer 1's token requesting customer 2's balance (QCC-API-009).
                'resourcePath' => self::RESOURCE_PATH . '/2',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];

        try {
            $this->_webApiCall($serviceInfo);
            self::fail('Expected an authorization exception but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals(HttpExceptionCodes::HTTP_FORBIDDEN, $e->getCode());
        }
    }

    public function testUnauthenticatedRequestIsRejected(): void
    {
        $this->_markTestAsRestOnly();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/1',
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
        ];

        try {
            $this->_webApiCall($serviceInfo);
            self::fail('Expected an authentication exception but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals(HttpExceptionCodes::HTTP_UNAUTHORIZED, $e->getCode());
        }
    }

    /**
     * QCC-API-018: a well-formed but nonexistent customerId resolves to 404
     * CUSTOMER_NOT_FOUND, using an admin-token caller (ACL resource
     * ICC_QuickConsultCredit::credit) so the "self" ownership check does not interfere.
     */
    public function testUnknownCustomerIdReturns404(): void
    {
        $this->_markTestAsRestOnly();
        $token = $this->adminTokenService->createAdminAccessToken(
            TestBootstrap::ADMIN_NAME,
            TestBootstrap::ADMIN_PASSWORD
        );

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/999999',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];

        try {
            $this->_webApiCall($serviceInfo);
            self::fail('Expected a not-found exception but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals(HttpExceptionCodes::HTTP_NOT_FOUND, $e->getCode());
            $error = $this->processRestExceptionResult($e);
            self::assertSame('CUSTOMER_NOT_FOUND', $error['error_code']);
        }
    }

    /**
     * QCC-API-018: a malformed/zero/negative customerId path parameter is rejected with
     * HTTP 401 and error_code CUSTOMER_NOT_FOUND, distinct from the 404
     * well-formed-but-nonexistent case above.
     */
    public function testMalformedCustomerIdReturns401(): void
    {
        $this->_markTestAsRestOnly();
        $token = $this->adminTokenService->createAdminAccessToken(
            TestBootstrap::ADMIN_NAME,
            TestBootstrap::ADMIN_PASSWORD
        );

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/0',
                'httpMethod' => Request::HTTP_METHOD_GET,
                'token' => $token,
            ],
        ];

        try {
            $this->_webApiCall($serviceInfo);
            self::fail('Expected an unauthorized exception but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals(HttpExceptionCodes::HTTP_UNAUTHORIZED, $e->getCode());
            $error = $this->processRestExceptionResult($e);
            self::assertSame('CUSTOMER_NOT_FOUND', $error['error_code']);
        }
    }
}
