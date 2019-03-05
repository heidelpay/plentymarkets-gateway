<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Exceptions\SecurityHashInvalidException;
use Heidelpay\Models\Transaction;
use Heidelpay\Services\Database\TransactionService;
use Heidelpay\Services\NotificationServiceContract;
use Heidelpay\Services\PaymentService;
use Heidelpay\Services\UrlServiceContract;
use Heidelpay\Traits\Translator;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

/**
 * Processes the transaction/payment responses coming from the heidelpay payment system.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
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
    /** @var PaymentService */
    private $paymentService;
    /** @var TransactionService*/
    private $transactionService;
    /** @var NotificationServiceContract */
    private $notification;
    /** @var UrlServiceContract */
    private $urlService;

    /**
     * ResponseController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param PaymentService $paymentService
     * @param TransactionService $transactionService
     * @param NotificationServiceContract $notification
     * @param UrlServiceContract $urlService
     */
    public function __construct(
        Request $request,
        Response $response,
        PaymentService $paymentService,
        TransactionService $transactionService,
        NotificationServiceContract $notification,
        UrlServiceContract $urlService
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->paymentService = $paymentService;
        $this->transactionService = $transactionService;
        $this->notification = $notification;
        $this->urlService = $urlService;
    }

    /**
     * Creates a transaction object and returns bool to indicate success.
     *
     * @param $response
     * @param $responseObject
     *
     * @return bool
     * @throws \Heidelpay\Exceptions\SecurityHashInvalidException
     */
    private function createAndHandleTransaction($response, $responseObject): bool
    {
        try {
            $txn = $this->transactionService->getTransactionIfItExists($responseObject);

            // verify hash
            $this->transactionService->verifyTransaction($responseObject);

            if ($txn instanceof Transaction) {
                $message = 'response.debugTransactionAlreadyExists';
            } else {
                $txn = $this->transactionService->createTransaction($response);
                $message = 'response.debugCreatedTransaction';
            }

            $this->notification->debug($message, __METHOD__, ['Response' => $response, 'Transaction' => $txn]);

            if ($response['isSuccess'] && !$response['isPending']) {
                $this->paymentService->handleTransaction($txn);
            }
        } catch (\RuntimeException $e) {
            $this->notification->warning($e->getMessage(), __METHOD__, ['data' => ['data' => $response['response']]]);
            return false;
        }
        return true;
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
        // also scrap the 'lang' parameter which will be sent when e.g. Sofort is being used.
        $postResponse = $this->request->except(['plentyMarkets', 'lang']);
        ksort($postResponse);

        $response = $this->paymentService->handleAsyncPaymentResponse(['response' => $postResponse]);
        $responseObject = $response['response'];

        $logData = ['POST response' => $postResponse, 'response' => $response];
        $this->notification->debug('response.debugReceivedResponse', __METHOD__, $logData);

        // if something went wrong during the lib call, return the cancel url.
        // exceptionCode = problem inside of the lib, error = error during libCall.
        if (!isset($response['exceptionCode'])) {
            try {
                $this->createAndHandleTransaction($response, $responseObject);
                $validHash = true;
            } catch (SecurityHashInvalidException $e) {
                $this->notification->error($e->getMessage(), __METHOD__, ['Response' => $response]);
                $validHash = false;
            }

            // if the transaction is successful or pending, return the success url.
            if ($validHash && ($response['isSuccess'] || $response['isPending'])) {
                $this->notification->debug('response.debugReturnSuccessUrl', __METHOD__, ['Response' => $response]);
                return $this->urlService->generateURL(Routes::CHECKOUT_SUCCESS);
            }
        }

        $this->notification->debug('response.debugReturnFailureUrl', __METHOD__, ['Response' => $response]);
        return $this->urlService->generateURL(Routes::CHECKOUT_CANCEL);
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
        try {
            $this->createAndHandleTransaction($response, $responseObject);
        } catch (SecurityHashInvalidException $e) {
            $this->notification->error($e->getMessage(), __METHOD__, ['Response' => $response]);
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

    /**
     * Handles form requests which do not need any further action by the client.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleForm(): \Symfony\Component\HttpFoundation\Response
    {
        $this->notification->error('payment.errorDuringPaymentExecution', __METHOD__);
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
    //</editor-fold>
}
