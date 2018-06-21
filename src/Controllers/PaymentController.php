<?php

namespace Heidelpay\Controllers;

use Heidelpay\Services\NotificationServiceContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

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
    /**
     * @var Response
     */
    private $response;
    /**
     * @var NotificationServiceContract
     */
    private $notification;
    /**
     * @var Request
     */
    private $request;

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param NotificationServiceContract $notification
     */
    public function __construct(
        Request $request,
        Response $response,
        NotificationServiceContract $notification
    ) {
        $this->response = $response;
        $this->notification = $notification;
        $this->request = $request;
    }

    /**
     * @return BaseResponse
     */
    public function checkoutSuccess(): BaseResponse
    {
        $postResponse = $this->request->except(['plentyMarkets', 'lang']);

        $this->notification->success('payment.infoPaymentSuccessful', __METHOD__, [$postResponse]);
        return $this->response->redirectTo('place-order');
    }

    /**
     * @return BaseResponse
     */
    public function checkoutCancel(): BaseResponse
    {
        $this->notification->error('payment.errorDuringPaymentExecution', __METHOD__);
        return $this->response->redirectTo('checkout');
    }
}
