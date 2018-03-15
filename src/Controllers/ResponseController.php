<?php

namespace Heidelpay\Controllers;

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
     * Processes the incoming POST response.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function processResponse(): \Symfony\Component\HttpFoundation\Response
    {
        $this->getLogger(__METHOD__)->debug('heidelpay::response.receivedResponse');

        /** @var array $response */
        $response = $this->paymentService->handleAsyncPaymentResponse($this->request->all());

        $this->getLogger(__METHOD__)->error('response result', $response);

        // TODO: return to a success (or the default plentymarkets "after-create-order") page
        return $this->response->redirectTo('checkout');
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
