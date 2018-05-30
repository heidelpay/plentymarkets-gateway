<?php

namespace Heidelpay\Controllers;

use Heidelpay\Services\PaymentService;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;

/**
 * heidelpay Payment Controller
 *
 * Handles general processes that are interactions with the customer.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway
 */
class PaymentController extends Controller
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
        return $this->response->redirectTo('checkout');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendPaymentRequest(): \Symfony\Component\HttpFoundation\Response
    {
        // perform request
        // ack -> intern umleiten auf place-order
        // nok -> intern umleiten auf checkout


        $this->getLogger(__METHOD__)->error('Request', [$this->request]);

        return $this->response->redirectTo('checkout');
    }
}
