<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Helper\PaymentHelper;
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

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param PaymentHelper $paymentHelper
     * @param PaymentService $paymentService
     * @param TransactionService $transactionService
     * @param NotificationServiceContract $notification
     */
    public function __construct(
        Request $request,
        Response $response,
        PaymentHelper $paymentHelper,
        PaymentService $paymentService,
        TransactionService $transactionService,
        NotificationServiceContract $notification
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->paymentHelper = $paymentHelper;
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
        $this->notification = $notification;
    }

    //<editor-fold desc="Handlers">
    /**
     * Process the incoming POST response and return the redirect url depending on the response result.
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
        $responseObject = $response['response'];

        $logData = ['POST response' => $postResponse, 'response' => $response];
        $this->notification->debug('response.debugReceivedResponse', __METHOD__, $logData);

        // if something went wrong during the lib call, return the cancel url.
        // exceptionCode = problem inside of the lib, error = error during libCall.
        if (!isset($response['exceptionCode'])) {
            $this->createAndHandleTransaction($response, $responseObject);

            // if the transaction is successful or pending, return the success url.
            if ($response['isSuccess'] || $response['isPending']) {
                $this->notification->error('Return success url', __METHOD__, ['Response' => $response]);
                return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_SUCCESS;
            }
        }

        $this->notification->error('Return failure url', __METHOD__, ['Response' => $response]);
        return $this->paymentHelper->getDomain() . '/' . Routes::CHECKOUT_CANCEL;
    }

    /**
     * Always returns success to avoid endless pushing.
     *
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

        // do not handle error (always return success)
        $this->createAndHandleTransaction($response, $responseObject);

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
        return $this->makeResponse($message);
    }

    /**
     * Creates a transaction object and returns bool to indicate success.
     *
     * @param $response
     * @param $responseObject
     *
     * @return bool
     */
    private function createAndHandleTransaction($response, $responseObject): bool
    {
        try {
            /* todo: refactor -> move check and so on to Transaction service */
            $txn = $this->transactionService->getTransactionIfItExists($responseObject);

            if ($txn instanceof Transaction) {
                $message = 'response.debugTransactionAlreadyExists';
            } else {
                $txn = $this->transactionService->createTransaction($response);
                $message = 'response.debugCreatedTransaction';
            }

            $this->notification->debug($message, __METHOD__, ['Response' => $response, 'Transaction' => $txn]);
            /* todo: all in between can be refactored */

            if ($response['isSuccess'] && !$response['isPending']) {
                $this->paymentService->handleTransaction($txn, $responseObject);
            }
        } catch (\RuntimeException $e) {
            $this->notification->warning($e->getMessage(), __METHOD__, ['data' => ['data' => $response['response']]]);
            return false;
        }
        return true;
    }
    //</editor-fold>
}
