<?php

namespace Heidelpay\Controllers;

use Heidelpay\Services\NotificationServiceContract;
use Plenty\Plugin\Controller;
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
     * ResponseController constructor.
     *
     * @param Response $response
     * @param NotificationServiceContract $notification
     */
    public function __construct(
        Response $response,
        NotificationServiceContract $notification
    ) {
        $this->response = $response;
        $this->notification = $notification;
    }

    /**
     * @return BaseResponse
     */
    public function checkoutSuccess(): BaseResponse
    {
        $this->notification->success('payment.infoPaymentSuccessful', __METHOD__);
        return $this->response->redirectTo('checkout');
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
