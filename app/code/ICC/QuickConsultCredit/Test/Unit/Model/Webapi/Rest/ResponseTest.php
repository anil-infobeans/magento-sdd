<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Test\Unit\Model\Webapi\Rest;

use ICC\QuickConsultCredit\Model\Service\Exception\InvalidAmountException;
use ICC\QuickConsultCredit\Model\Webapi\Rest\Response;
use Magento\Framework\App\State;
use Magento\Framework\Webapi\ErrorProcessor;
use Magento\Framework\Webapi\Rest\Response\RendererFactory;
use Magento\Framework\Webapi\Rest\Response\RendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers the standardized `{ success: false, error_code, message }` error-response
 * shape (QCC-API-012/017/019, QCC-SEC-005/006), asserting it is applied only for this
 * module's own exceptions and that it never leaks internal exception details
 * (QCC-NFR-006).
 */
class ResponseTest extends TestCase
{
    /**
     * @var RendererInterface&MockObject
     */
    private $renderer;

    /**
     * @var ErrorProcessor&MockObject
     */
    private $errorProcessor;

    /**
     * @var State&MockObject
     */
    private $appState;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var array
     */
    private $renderedBody;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(RendererInterface::class);
        $this->renderer->method('getMimeType')->willReturn('application/json');
        $this->renderer->method('render')->willReturnCallback(function (array $data): string {
            $this->renderedBody = $data;
            return json_encode($data);
        });

        $rendererFactory = $this->createMock(RendererFactory::class);
        $rendererFactory->method('get')->willReturn($this->renderer);

        $this->errorProcessor = $this->createMock(ErrorProcessor::class);
        $this->appState = $this->createMock(State::class);
        $this->appState->method('getMode')->willReturn(State::MODE_PRODUCTION);

        $this->response = new Response($rendererFactory, $this->errorProcessor, $this->appState);
    }

    public function testCreditApiExceptionRendersSuccessFalseAndErrorCode(): void
    {
        $exception = new InvalidAmountException(__('Amount must be a positive whole number of credit points.'));
        $this->errorProcessor->method('maskException')->willReturn($exception);

        $this->response->setException($exception);
        $this->invokeRenderMessages();

        self::assertFalse($this->renderedBody['success']);
        self::assertSame('INVALID_AMOUNT', $this->renderedBody['error_code']);
        self::assertSame('Amount must be a positive whole number of credit points.', $this->renderedBody['message']);
    }

    public function testCreditApiExceptionMessageNeverIncludesTraceInProductionMode(): void
    {
        $exception = new InvalidAmountException(__('Amount must be a positive whole number of credit points.'));
        $this->errorProcessor->method('maskException')->willReturn($exception);

        $this->response->setException($exception);
        $this->invokeRenderMessages();

        self::assertArrayNotHasKey('trace', $this->renderedBody);
    }

    public function testUnrelatedExceptionDoesNotGainSuccessOrErrorCodeFields(): void
    {
        $exception = new \Magento\Framework\Webapi\Exception(__('Generic failure.'), 0, 500);
        $this->errorProcessor->method('maskException')->willReturn($exception);

        $this->response->setException($exception);
        $this->invokeRenderMessages();

        self::assertArrayNotHasKey('success', $this->renderedBody);
        self::assertArrayNotHasKey('error_code', $this->renderedBody);
    }

    /**
     * @return void
     */
    private function invokeRenderMessages(): void
    {
        $method = new \ReflectionMethod(Response::class, '_renderMessages');
        $method->setAccessible(true);
        $method->invoke($this->response);
    }
}
