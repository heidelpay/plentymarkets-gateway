<?php
/**
 * Provides service methods to handle responses from the payment api (ngw).
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2019-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */

namespace Heidelpay\Services;

use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use RuntimeException;
use function array_key_exists;
use function is_array;

class ResponseService implements ResponseServiceContract
{
    /** @var LibService $libService*/
    private $libService;

    /**
     * ResponseHandlerService constructor.
     *
     * @param LibService $libraryService
     */
    public function __construct(LibService $libraryService)
    {
        $this->libService = $libraryService;
    }

    /**
     * @param string $type
     * @param $response
     * @return mixed
     * @throws RuntimeException
     */
    public function handleSyncResponse(string $type, $response)
    {
        if (!is_array($response)) {
            return $response;
        }

        // return the exception message, if present.
        if (isset($response['exceptionCode'])) {
            throw new RuntimeException($response['exceptionCode']);
        }

        if (!$response['isSuccess']) {
            throw new RuntimeException($response['response']['PROCESSING.RETURN']);
        }

        // return rendered html content
        if ($type === GetPaymentMethodContent::RETURN_TYPE_HTML) {
            // return the payment frame url, if it is needed
            if (array_key_exists('FRONTEND.PAYMENT_FRAME_URL', $response['response'])) {
                return $response['response']['FRONTEND.PAYMENT_FRAME_URL'];
            }

            return $response['response']['FRONTEND.REDIRECT_URL'];
        }

        // return the redirect url, if present.
        if ($type === GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL) {
            return $response['response']['FRONTEND.REDIRECT_URL'];
        }


        return $response;
    }

    /**
     * Handles the asynchronous response coming from the heidelpay API.
     *
     * @param array $post
     *
     * @return array
     */
    public function handlePaymentResponse(array $post): array
    {
        return $this->libService->handleResponse($post);
    }

    /**
     * Calls the handler for the push notification processing.
     *
     * @param array $post
     *
     * @return array
     */
    public function handlePushNotification(array $post): array
    {
        return $this->libService->handlePushNotification($post);
    }
}
