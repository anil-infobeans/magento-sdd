<?php
/**
 * Copyright © ICC. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace ICC\QuickConsultCredit\Model\Webapi\Rest;

use ICC\QuickConsultCredit\Api\Exception\CreditApiExceptionInterface;

/**
 * Adds the standardized `{ "success": false, "error_code": "...", "message": "..." }`
 * fields (QCC-API-012) to the REST error response body, but only when the thrown
 * exception is one of this module's own {@see CreditApiExceptionInterface} exceptions.
 *
 * For every other exception in the system this preference behaves byte-for-byte
 * identically to \Magento\Framework\Webapi\Rest\Response — no plugin extension point
 * exists on the protected `_renderMessages()` method, so a preference is the least
 * intrusive mechanism available to add these fields without altering the response shape
 * of any other Magento REST API (Constitution Principle I, XII).
 */
class Response extends \Magento\Framework\Webapi\Rest\Response
{
    /**
     * @inheritDoc
     */
    protected function _renderMessages()
    {
        $responseHttpCode = null;
        $messageData = [];
        foreach ($this->getException() as $exception) {
            $maskedException = $this->_errorProcessor->maskException($exception);
            $messageData = [
                'message' => $maskedException->getMessage(),
            ];
            if ($maskedException->getErrors()) {
                $messageData['errors'] = [];
                foreach ($maskedException->getErrors() as $errorMessage) {
                    $errorData['message'] = $errorMessage->getRawMessage();
                    $errorData['parameters'] = $errorMessage->getParameters();
                    $messageData['errors'][] = $errorData;
                }
            }
            if ($maskedException->getCode()) {
                $messageData['code'] = $maskedException->getCode();
            }
            if ($maskedException->getDetails()) {
                $messageData['parameters'] = $maskedException->getDetails();
            }
            if ($this->_appState->getMode() == \Magento\Framework\App\State::MODE_DEVELOPER) {
                $messageData['trace'] = $exception instanceof \Magento\Framework\Webapi\Exception
                    ? $exception->getStackTrace()
                    : $exception->getTraceAsString();
            }

            if ($exception instanceof CreditApiExceptionInterface) {
                $messageData = [
                        'success' => false,
                        'error_code' => $exception->getErrorCode(),
                    ] + $messageData;
            }

            $responseHttpCode = $maskedException->getHttpCode();
        }
        // set HTTP code of the last error, Content-Type, and all rendered error messages to body
        $this->setHttpResponseCode($responseHttpCode);
        $this->setMimeType($this->_renderer->getMimeType());
        $this->setBody($this->_renderer->render($messageData));
        return $this;
    }
}
