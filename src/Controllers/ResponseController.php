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
     * @return void
     */
    public function processResponse(): void
    {
        $this->getLogger(__METHOD__)->info('Heidelpay::response.received');

        /** @var array $response */
        $response = $this->paymentService->handleAsyncPaymentResponse($this->request->all());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkoutSuccess(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->response->redirectTo('place-order');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkoutCancel(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->response->redirectTo('place-order');
    }
}
