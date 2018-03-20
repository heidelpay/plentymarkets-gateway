<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Services\PaymentService;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;

/**
 * heidelpay Response Controller
 *
 * Processes the transaction/payment responses coming from the heidelpay payment system.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\controllers
 */
class ResponseController extends Controller
{
    use Loggable;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * ResponseController constructor.
     *
     * @param Request        $request
     * @param Response       $response
     * @param PaymentService $paymentService
     */
    public function __construct(Request $request, Response $response, PaymentService $paymentService)
    {
        $this->request = $request;
        $this->response = $response;
        $this->paymentService = $paymentService;
    }

    /**
     * Processes the incoming POST response and returns
     * an action url depending on the response result.
     *
     * @return string
     */
    public function processResponse(): string
    {
        $response = $this->paymentService->handleAsyncPaymentResponse([
            'response' => $this->request->all()
        ]);

        $this->getLogger('heidelpay async response')->error('heidelpay::response.receivedResponse', [
            'response' => $response
        ]);

        // if something went wrong during the lib call, return the cancel url.
        if (isset($response['exceptionCode'])) {
            return Routes::CHECKOUT_CANCEL;
        }

        // if the transaction is successful or pending, return the success url.
        if ($response['isSuccess'] || $response['isPending']) {
            return Routes::CHECKOUT_SUCCESS;
        }

        return Routes::CHECKOUT_CANCEL;
    }

    /**
     * When the processResponse cannot be accessed, or something went wrong during the process,
     * the heidelpay API redirects to the processResponse url using GET instead of POST.
     * This method is for handling this "emergency" behaviour.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function emergencyRedirect(): \Symfony\Component\HttpFoundation\Response
    {
        $this->getLogger(__METHOD__)->warning('heidelpay::response.emergency');

        return $this->response->redirectTo('checkout');
    }
}
