<?php

namespace Heidelpay\Controllers;

use IO\Services\NotificationService;
use Plenty\Plugin\Controller;
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
     * @var Response
     */
    private $response;
    /**
     * @var NotificationService
     */
    private $notification;

    /**
     * ResponseController constructor.
     *
     * @param Response $response
     * @param NotificationService $notification
     */
    public function __construct(
        Response $response,
        NotificationService $notification
    ) {
        $this->response = $response;
        $this->notification = $notification;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkoutSuccess(): \Symfony\Component\HttpFoundation\Response
    {
        $this->notification->success('heidelpay::payment.infoPaymentSuccessful');
        return $this->response->redirectTo('place-order');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkoutCancel(): \Symfony\Component\HttpFoundation\Response
    {
        $this->notification->error('heidelpay::payment.errorDuringPaymentExecution');
        return $this->response->redirectTo('checkout');
    }
}
