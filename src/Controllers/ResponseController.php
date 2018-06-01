<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Transaction;
use Heidelpay\Services\Database\TransactionService;
use Heidelpay\Services\PaymentService;
use IO\Services\NotificationService;
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
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var TransactionService
     */
    private $transactionService;
    /**
     * @var NotificationService
     */
    private $notification;

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param PaymentHelper $paymentHelper
     * @param PaymentService $paymentService
     * @param TransactionService $transactionService
     * @param NotificationService $notification
     */
    public function __construct(
        Request $request,
        Response $response,
        PaymentHelper $paymentHelper,
        PaymentService $paymentService,
        TransactionService $transactionService,
        NotificationService $notification
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->paymentHelper = $paymentHelper;
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
        $this->notification = $notification;
    }

    /**
     * Processes the incoming POST response and returns
     * an action url depending on the response result.
     *
     * @return string
     */
    public function processAsyncResponse(): string
    {
        // get all post parameters except the 'plentyMarkets' one injected by the plentymarkets core.
        // also scrap the 'lang' parameter which will be sent when e.g. PayPal is being used.
        $postResponse = $this->request->except(['plentyMarkets', 'lang']);
        ksort($postResponse);

        $response = $this->paymentService->handleAsyncPaymentResponse(['response' => $postResponse]);
        $this->getLogger(__METHOD__)->debug('heidelpay::response.debugReceivedResponse', [
            'POST response' =>$postResponse,
            'response' => $response
        ]);

        // if something went wrong during the lib call, return the cancel url.
        // exceptionCode = problem inside of the lib, error = error during libCall.
        if (isset($response['exceptionCode'])) {
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
        }

        // create the transaction entity.
        $newTransaction = $this->transactionService->createTransaction($response);
        if ($newTransaction === null || ! $newTransaction instanceof Transaction) {
            $this->getLogger(__METHOD__)->error('heidelpay::response.errorTransactionNotCreated', [
                'data' => $response['response']
            ]);

            $this->notification->error('heidelpay::payment.errorTransactionNotCreated');
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
        }

        // if the transaction is successful or pending, return the success url.
        if ($response['isSuccess'] || $response['isPending']) {
            $this->notification->success('heidelpay::payment.infoPaymentSuccessful');
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_SUCCESS;
        }

        $this->notification->error('heidelpay::payment.errorDuringPaymentExecution');
        return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
    }

    /**
     * When the processAsyncResponse cannot be accessed, or something went wrong during the process,
     * the heidelpay API redirects to the processAsyncResponse url using GET instead of POST.
     * This method is for handling this behaviour.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function emergencyRedirect(): \Symfony\Component\HttpFoundation\Response
    {
        $this->getLogger(__METHOD__)->warning('heidelpay::response.warningResponseCalledInInvalidContext');

        return $this->response->redirectTo('checkout');
    }

    /**
     * @return Response
     */
    public function processPush(): Response
    {
        $postPayload = $this->request->getContent();
        $this->getLogger(__METHOD__)->debug('heidelpay::response.debugPushNotificationReceived', [
            'content' => $postPayload,
        ]);

        $response = $this->paymentService->handlePushNotification(['xmlContent' => $postPayload]);

        if (isset($response['exceptionCode'])) {
            $this->getLogger(__METHOD__)->error('heidelpay::error.errorResponseContainsErrorCode');
            return $this->response->make('Not Ok.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->response->make('OK', Response::HTTP_OK);
    }
}
