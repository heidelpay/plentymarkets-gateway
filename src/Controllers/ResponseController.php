<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Constants\TransactionType;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Models\Contracts\OrderTxnIdRelationRepositoryContract;
use Heidelpay\Models\OrderTxnIdRelation;
use Heidelpay\Models\Transaction;
use Heidelpay\Services\Database\TransactionService;
use Heidelpay\Services\NotificationServiceContract;
use Heidelpay\Services\PaymentService;
use Heidelpay\Traits\Translator;
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
    use Translator;

    /** @var Request $request */
    private $request;

    /** @var Response */
    private $response;

    /** @var PaymentHelper */
    private $paymentHelper;

    /** @var PaymentService */
    private $paymentService;

    /** @var TransactionService*/
    private $transactionService;

    /** @var NotificationServiceContract */
    private $notification;

    /** @var OrderTxnIdRelationRepositoryContract */
    private $orderTxnIdRepo;

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param PaymentHelper $paymentHelper
     * @param PaymentService $paymentService
     * @param TransactionService $transactionService
     * @param NotificationServiceContract $notification
     * @param OrderTxnIdRelationRepositoryContract $orderTxnIdRepo
     */
    public function __construct(
        Request $request,
        Response $response,
        PaymentHelper $paymentHelper,
        PaymentService $paymentService,
        TransactionService $transactionService,
        NotificationServiceContract $notification,
        OrderTxnIdRelationRepositoryContract $orderTxnIdRepo
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->paymentHelper = $paymentHelper;
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
        $this->notification = $notification;
        $this->orderTxnIdRepo = $orderTxnIdRepo;
    }

    //<editor-fold desc="Handlers">
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

        // create transaction
        try {
            $newTransaction = $this->transactionService->createTransaction($response);
            $this->notification
                ->debug('response.debugCreatedTransaction', __METHOD__, ['Transaction' => $newTransaction]);
        } catch (\Exception $e) {
            $this->notification->error($e->getMessage(), __METHOD__, ['data' => ['data' => $response['response']]]);
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
        }

        // if the transaction is successful or pending, return the success url.
        if ($response['isSuccess'] || $response['isPending']) {
            return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_SUCCESS;
        }

        return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
    }

    /**
     * @return Response
     */
    public function processPush(): Response
    {
        $postPayload = $this->request->getContent();
        $this->notification->debug('response.debugPushNotificationReceived', __METHOD__, ['content' => $postPayload]);
        $response = $this->paymentService->handlePushNotification(['xmlContent' => $postPayload]);
        $responseObject = $response['response'];

        if (isset($response['exceptionCode'])) {
            return $this->makeError('error.errorResponseContainsErrorCode', ['Response' => $response]);
        }

        // return success, if transaction already exists, to avoid endless pushing
        if ($this->transactionService->checkTransactionAlreadyExists($responseObject)) {
            return $this->makeSuccess('response.debugTransactionAlreadyExists', ['Transaction' => $response]);
        }

        try {
            $txn = $this->transactionService->createTransaction($response);
            $this->notification->debug('response.debugCreatedTransaction', __METHOD__, ['Transaction' => $txn]);

            if ($response['isSuccess'] && !$response['isPending']) {
                $this->handleTransaction($txn, $responseObject);
            }
        } catch (\RuntimeException $e) {
            return $this->makeError($e->getMessage(), [$responseObject]);
        }

        return $this->makeSuccess('general.debugSuccess', []);
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
    //</editor-fold>

    //<editor-fold desc="Helpers">
    /**
     * Handles the given transaction by type
     *
     * @param Transaction $txn
     * @param Response $responseObject
     *
     * @throws \RuntimeException
     */
    protected function handleTransaction(Transaction $txn, Response $responseObject)
    {
        $transactionCode = $this->paymentHelper->getTransactionCode($responseObject);

        switch ($transactionCode) {
            case TransactionType::HP_CAPTURE:
                $this->handleCapturePush($txn);
                break;
            case TransactionType::HP_AUTHORIZE:         // intended fall-through
            case TransactionType::HP_REGISTRATION:      // intended fall-through
            case TransactionType::HP_DEBIT:             // intended fall-through
            case TransactionType::HP_CHARGEBACK:        // intended fall-through
            case TransactionType::HP_CREDIT:            // intended fall-through
            case TransactionType::HP_DEREGISTRATION:    // intended fall-through
            case TransactionType::HP_FINALIZE:          // intended fall-through
            case TransactionType::HP_INITIALIZE:        // intended fall-through
            case TransactionType::HP_REBILL:            // intended fall-through
            case TransactionType::HP_RECEIPT:           // intended fall-through
            case TransactionType::HP_REFUND:            // intended fall-through
            case TransactionType::HP_REREGISTRATION:    // intended fall-through
            case TransactionType::HP_REVERSAL:          // intended fall-through
            default:
                // do nothing if the given Transaction needs no handling
                break;
        }
    }

    /**
     * @param $txn
     *
     * @throws \RuntimeException
     */
    protected function handleCapturePush($txn)
    {
        $relation = $this->orderTxnIdRepo->getOrderTxnIdRelationByTxnId($txn->txnId);

        if (!$relation instanceof OrderTxnIdRelation) {
            throw new \RuntimeException('response.errorOrderTxnIdRelationNotFound');
        }

        $this->paymentService->createPlentyPayment($txn, $relation->mopId, $relation->orderId);
    }
    //</editor-fold>

    //<editor-fold desc="Responses">
    /**
     * @param $message
     * @param int $code
     * @return Response
     */
    protected function makeResponse($message, $code = Response::HTTP_OK): Response
    {
        return $this->response->make($this->getTranslator()->trans($message), $code);
    }

    /**
     * @param $message
     * @param $logData
     * @return Response
     */
    protected function makeSuccess($message, $logData): Response
    {
        $this->notification->debug($message, __METHOD__, $logData);
        return $this->makeResponse($message);
    }

    /**
     * @param $message
     * @param $logData
     * @return Response
     */
    protected function makeError($message, $logData): Response
    {
        $this->notification->error($message, __METHOD__, $logData, true);
        return $this->makeResponse($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    //</editor-fold>
}
