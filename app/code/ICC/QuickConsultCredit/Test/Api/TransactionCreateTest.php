<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Api;

use ICC\QuickConsultCredit\Api\CreditTransactionManagementInterface;
use ICC\QuickConsultCredit\Api\Data\CreditTransactionInterface;
use Magento\Framework\Webapi\Exception as HttpExceptionCodes;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Api\AdminTokenServiceInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Bootstrap as TestBootstrap;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * API contract test for `POST /V1/quick-consult-credit/transactions`
 * (QCC-API-005/010/011/012/013/017/019, CLA-007/016/020 resolved).
 */
class TransactionCreateTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/quick-consult-credit/transactions';

    /**
     * @var AdminTokenServiceInterface
     */
    private $adminTokenService;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp(): void
    {
        $this->adminTokenService = Bootstrap::getObjectManager()->get(AdminTokenServiceInterface::class);
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testAuthorizedRedeemSucceedsWithExpectedResponseShape(): void
    {
        $this->_markTestAsRestOnly();
        $this->creditFixtureCustomer(1, 100);

        $response = $this->callTransactionsApi($this->adminToken(), [
            'customerId' => 1,
            'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            'amount' => 40,
        ]);

        self::assertArrayHasKey('transaction_id', $response);
        self::assertSame(CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, $response['type']);
        self::assertSame(40, $response['amount']);
        self::assertSame(100, $response['previous_balance']);
        self::assertSame(60, $response['current_balance']);
    }

    /**
     * QCC-API-019/CLA-020: a missing required field is rejected as INVALID_REQUEST.
     */
    public function testMissingFieldReturnsInvalidRequest(): void
    {
        $this->_markTestAsRestOnly();

        $this->assertRejected(
            ['transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 'amount' => 10],
            HttpExceptionCodes::HTTP_BAD_REQUEST,
            'INVALID_REQUEST'
        );
    }

    /**
     * QCC-API-019/CLA-020: a non-integer/fractional amount is INVALID_REQUEST, distinct
     * from a structurally valid but out-of-range amount (INVALID_AMOUNT).
     */
    public function testFractionalAmountReturnsInvalidRequest(): void
    {
        $this->_markTestAsRestOnly();

        $this->assertRejected(
            ['customerId' => 1, 'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 'amount' => 25.5],
            HttpExceptionCodes::HTTP_BAD_REQUEST,
            'INVALID_REQUEST'
        );
    }

    /**
     * QCC-API-017/CLA-016: only REDEEM is accepted; PURCHASE/ADMIN_ADD/ADMIN_REMOVE are
     * rejected as INVALID_TRANSACTION_TYPE before any balance-changing logic runs.
     *
     * @dataProvider nonRedeemTransactionTypeDataProvider
     */
    public function testNonRedeemTransactionTypeReturnsInvalidTransactionType(string $transactionType): void
    {
        $this->_markTestAsRestOnly();

        $this->assertRejected(
            ['customerId' => 1, 'transactionType' => $transactionType, 'amount' => 10],
            HttpExceptionCodes::HTTP_BAD_REQUEST,
            'INVALID_TRANSACTION_TYPE'
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function nonRedeemTransactionTypeDataProvider(): array
    {
        return [
            'purchase' => [CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE],
            'admin_add' => [CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_ADD],
            'admin_remove' => [CreditTransactionInterface::TRANSACTION_TYPE_ADMIN_REMOVE],
        ];
    }

    /**
     * QCC-API-017: a structurally valid but zero/negative amount is INVALID_AMOUNT.
     */
    public function testZeroAmountReturnsInvalidAmount(): void
    {
        $this->_markTestAsRestOnly();

        $this->assertRejected(
            ['customerId' => 1, 'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 'amount' => 0],
            HttpExceptionCodes::HTTP_BAD_REQUEST,
            'INVALID_AMOUNT'
        );
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testInsufficientBalanceIsRejected(): void
    {
        $this->_markTestAsRestOnly();
        $this->creditFixtureCustomer(1, 5);

        $this->assertRejected(
            ['customerId' => 1, 'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 'amount' => 10],
            HttpExceptionCodes::HTTP_BAD_REQUEST,
            'INSUFFICIENT_BALANCE'
        );
    }

    public function testUnknownCustomerIsRejected(): void
    {
        $this->_markTestAsRestOnly();

        $this->assertRejected(
            ['customerId' => 999999, 'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM, 'amount' => 10],
            HttpExceptionCodes::HTTP_NOT_FOUND,
            'CUSTOMER_NOT_FOUND'
        );
    }

    /**
     * QCC-API-005/CLA-007: no customer self-access on this endpoint — a customer-session
     * caller is rejected as UNAUTHORIZED even for their own customerId.
     */
    public function testCustomerSessionCallerIsUnauthorized(): void
    {
        $this->_markTestAsRestOnly();
        $token = $this->customerTokenService->createCustomerAccessToken('customer@example.com', 'password');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];

        try {
            $this->_webApiCall($serviceInfo, [
                'customerId' => 1,
                'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
                'amount' => 10,
            ]);
            self::fail('Expected an authorization exception but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals(HttpExceptionCodes::HTTP_FORBIDDEN, $e->getCode());
        }
    }

    /**
     * QCC-API-012 AC-4/QCC-SEC-007 AC-2: an unauthorized caller submitting a
     * simultaneously invalid payload (missing field) must receive UNAUTHORIZED, never
     * INVALID_REQUEST — authentication/authorization precedes request validation.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testUnauthorizedCallerWithInvalidPayloadReceivesUnauthorizedNotInvalidRequest(): void
    {
        $this->_markTestAsRestOnly();
        $token = $this->customerTokenService->createCustomerAccessToken('customer@example.com', 'password');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $token,
            ],
        ];

        try {
            // Missing "amount" — would normally be INVALID_REQUEST, but authorization is
            // evaluated first for an unaffiliated/customer-session caller.
            $this->_webApiCall($serviceInfo, [
                'customerId' => 1,
                'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            ]);
            self::fail('Expected an authorization exception but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals(HttpExceptionCodes::HTTP_FORBIDDEN, $e->getCode());
        }
    }

    /**
     * QCC-API-013/CLA-004: a resubmitted (replayed) request is validated independently
     * against the current balance — it MAY produce an additional accepted transaction.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testReplayedRequestIsIndependentlyValidatedAndMayBeAcceptedAgain(): void
    {
        $this->_markTestAsRestOnly();
        $this->creditFixtureCustomer(1, 100);

        $requestData = [
            'customerId' => 1,
            'transactionType' => CreditTransactionInterface::TRANSACTION_TYPE_REDEEM,
            'amount' => 10,
        ];
        $token = $this->adminToken();

        $first = $this->callTransactionsApi($token, $requestData);
        $second = $this->callTransactionsApi($token, $requestData);

        self::assertNotSame($first['transaction_id'], $second['transaction_id']);
        self::assertSame(90, $first['current_balance']);
        self::assertSame(80, $second['current_balance']);
    }

    /**
     * @param string $adminToken
     * @param array $requestData
     * @return array
     */
    private function callTransactionsApi(string $adminToken, array $requestData): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $adminToken,
            ],
        ];

        return $this->_webApiCall($serviceInfo, $requestData);
    }

    /**
     * @param array $requestData
     * @param int $expectedHttpCode
     * @param string $expectedErrorCode
     * @return void
     */
    private function assertRejected(array $requestData, int $expectedHttpCode, string $expectedErrorCode): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $this->adminToken(),
            ],
        ];

        try {
            $this->_webApiCall($serviceInfo, $requestData);
            self::fail('Expected a rejection but the call succeeded.');
        } catch (\Exception $e) {
            self::assertEquals($expectedHttpCode, $e->getCode());
            $error = $this->processRestExceptionResult($e);
            self::assertSame($expectedErrorCode, $error['error_code']);
        }
    }

    /**
     * @return string
     */
    private function adminToken(): string
    {
        return $this->adminTokenService->createAdminAccessToken(
            TestBootstrap::ADMIN_NAME,
            TestBootstrap::ADMIN_PASSWORD
        );
    }

    /**
     * Seed a customer's balance directly via the service layer (not the REST endpoint
     * under test) so that redemption tests start from a known balance.
     *
     * @param int $customerId
     * @param int $amount
     * @return void
     */
    private function creditFixtureCustomer(int $customerId, int $amount): void
    {
        /** @var CreditTransactionManagementInterface $transactionManagement */
        $transactionManagement = Bootstrap::getObjectManager()->create(CreditTransactionManagementInterface::class);
        $transactionManagement->credit(
            $customerId,
            $amount,
            CreditTransactionInterface::TRANSACTION_TYPE_PURCHASE,
            CreditTransactionInterface::SOURCE_SYSTEM,
            null,
            null,
            'test-seed-' . $customerId . '-' . uniqid('', true)
        );
    }
}
