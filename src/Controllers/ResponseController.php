<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Repositories\PaymentTxnIdRelationRepositoryContract;
use Heidelpay\Models\Transaction;
use Heidelpay\Services\Database\TransactionService;
use Heidelpay\Services\NotificationServiceContract;
use Heidelpay\Services\PaymentService;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

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
     * @var NotificationServiceContract
     */
    private $notification;
    /**
     * @var PaymentTxnIdRelationRepositoryContract
     */
    private $paymentTxnIdRelRepo;
    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepo;

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param PaymentHelper $paymentHelper
     * @param PaymentService $paymentService
     * @param TransactionService $transactionService
     * @param NotificationServiceContract $notification
     * @param PaymentTxnIdRelationRepositoryContract $paymentTxnIdRelRepo
     * @param PaymentRepositoryContract $paymentRepo
     */
    public function __construct(
        Request $request,
        Response $response,
        PaymentHelper $paymentHelper,
        PaymentService $paymentService,
        TransactionService $transactionService,
        NotificationServiceContract $notification,
        PaymentTxnIdRelationRepositoryContract $paymentTxnIdRelRepo,
        PaymentRepositoryContract $paymentRepo
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->paymentHelper = $paymentHelper;
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
        $this->notification = $notification;
        $this->paymentTxnIdRelRepo = $paymentTxnIdRelRepo;
        $this->paymentRepo = $paymentRepo;
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

        $logData = ['POST response' => $postResponse, 'response' => $response];
        $this->notification->debug('response.debugReceivedResponse', __METHOD__, $logData);

        // if something went wrong during the lib call, return the cancel url.
        // exceptionCode = problem inside of the lib, error = error during libCall.
        if (isset($response['exceptionCode'])) {
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
        }

        // create the transaction entity.
        $newTransaction = $this->transactionService->createTransaction($response);
        if ($newTransaction === null || ! $newTransaction instanceof Transaction) {
            $logData1 = ['data' => $response['response']];
            $this->notification->error('response.errorTransactionNotCreated', __METHOD__, $logData1);

            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
        }

        $this->notification->debug('response.debugCreatedTransaction', __METHOD__, ['Transaction' => $newTransaction]);

        // if the transaction is successful or pending, return the success url.
        if ($response['isSuccess'] || $response['isPending']) {
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_SUCCESS;
        }

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
        $this->notification->warning('response.warningResponseCalledInInvalidContext', __METHOD__);
        return $this->response->redirectTo('checkout');
    }

    /**
     * @return Response
     */
    public function processPush(): Response
    {
        $postPayload = $this->request->getContent();

        $this->notification->debug('response.debugPushNotificationReceived', __METHOD__, ['content' => $postPayload]);

        $response = $this->paymentService->handlePushNotification(['xmlContent' => $postPayload]);

        if (isset($response['exceptionCode'])) {
            $logData = ['Response' => $response];
            $this->notification->critical('error.errorResponseContainsErrorCode', __METHOD__, $logData);
            return $this->response->make('Not Ok.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($response['isSuccess'] && !$response['isPending'] && array_key_exists('response', $response)) {
            // todo: if it is a capture to a PA get the payment using txnId and set receivedAt and amount
            // todo: what if there are several captures?
            // todo: save transaction?

            $responseObject = $response['response'];

            $code = $responseObject['PAYMENT.CODE'];
            $txnId = $responseObject['IDENTIFICATION.TRANSACTIONID'];

            $paymentCodeParts = explode('.', $code);
            if (\count($paymentCodeParts) > 1 && $paymentCodeParts[1] === 'CP') {
                $payment = $this->paymentRepo->getPaymentById($this->paymentTxnIdRelRepo->getPaymentIdByTxnId($txnId));
                $payment->receivedAt = $responseObject['PROCESSING.TIMESTAMP'];
                $payment->amount = $responseObject['PRESENTATION.AMOUNT'];
                $this->paymentRepo->updatePayment($payment);

                $this->notification->error('payment', __METHOD__, [$payment], true);
            }


        }

        return $this->response->make('OK', Response::HTTP_OK);
    }
}
