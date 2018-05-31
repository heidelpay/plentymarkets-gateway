<?php

namespace Heidelpay\Controllers;

use Heidelpay\Services\PaymentService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
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
     * @var BasketRepositoryContract
     */
    private $basketRepo;

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param PaymentService $paymentService
     * @param BasketRepositoryContract $basketRepo
     */
    public function __construct(
        Request $request,
        Response $response,
        PaymentService $paymentService,
        BasketRepositoryContract $basketRepo
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->paymentService = $paymentService;
        $this->basketRepo = $basketRepo;
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
     * @throws \RuntimeException
     */
    public function sendPaymentRequest(): \Symfony\Component\HttpFoundation\Response
    {
        // perform request
        // ack -> intern umleiten auf place-order
        // nok -> intern umleiten auf checkout
        $postRequest = $this->request->except(['plentyMarkets', 'lang', 'mopId', 'paymentMethod']);
        $mopId = $this->request->only(['mopId'])['mopId'];
        $paymentMethod = $this->request->only(['paymentMethod'])['paymentMethod'];
        $transactionType = $this->paymentService->getTransactionType($paymentMethod);
        $basket = $this->basketRepo->load();

        $this->getLogger(__METHOD__)
            ->error('Request', [$postRequest, $mopId, $paymentMethod, $transactionType, $basket]);

        $this->paymentService->sendPaymentRequest($basket, $paymentMethod, $transactionType, $mopId);


        return $this->response->redirectTo('checkout');
    }
}
