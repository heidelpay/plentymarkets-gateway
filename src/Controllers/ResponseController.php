<?php

namespace Heidelpay\Controllers;

use Heidelpay\Constants\Routes;
use Heidelpay\Exceptions\SecurityHashInvalidException;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\PaymentMethodContract;
use Heidelpay\Models\Transaction;
use Heidelpay\Services\Database\TransactionService;
use Heidelpay\Services\NotificationServiceContract;
use Heidelpay\Services\PaymentService;
use Heidelpay\Services\UrlServiceContract;
use Heidelpay\Traits\Translator;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

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

    //<editor-fold desc="Helpers">
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



    /**
     * Returns the salutation from the post request.
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getSalutation(): string
    {
        if ($this->request->exists('customer_salutation')) {
            return $this->request->get('customer_salutation');
        }

        throw new \RuntimeException('Salutation not set!');
    }

    /**
     * Returns the date of birth from the request.
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getDateOfBirth(): string
    {
        if ($this->request->exists('customer_dob_day') &&
            $this->request->exists('customer_dob_month') &&
            $this->request->exists('customer_dob_year')) {
            return implode(
                             '-',
                             [
                                 $this->request->get('customer_dob_year'),
                                 $this->request->get('customer_dob_month'),
                                 $this->request->get('customer_dob_day')
                             ]
                         );
        }

        throw new \RuntimeException('Date of birth not set!');
    }
    //</editor-fold>

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

        // if the transaction is successful or pending, return the success url.
        try {
            $this->processResponse($postResponse);
        } catch (\RuntimeException $e) {
            $this->notification->debug('response.debugReturnFailureUrl', __METHOD__, ['Response' => $e->getMessage()]);
            return $this->urlService->generateURL(Routes::CHECKOUT_CANCEL);
        }

        $this->notification->debug('response.debugReturnSuccessUrl', __METHOD__, ['Error' => $postResponse]);
        return $this->urlService->generateURL(Routes::CHECKOUT_SUCCESS);
    }

    /**
     * Handles form requests which do not need any further action by the client.
     *
     * @param BasketRepositoryContract $basketRepo
     * @param PaymentHelper $paymentHelper
     * @return BaseResponse
     * @throws \RuntimeException
     */
    public function handleSyncRequest(
        BasketRepositoryContract $basketRepo,
        PaymentHelper $paymentHelper
    ): BaseResponse {
        $basket = $basketRepo->load();

        $mopId          = $basket->methodOfPaymentId;
        $paymentMethod  = $paymentHelper->mapMopToPaymentMethod($mopId);
        $methodInstance = $paymentHelper->getPaymentMethodInstanceByMopId($mopId);
        if (!$methodInstance instanceof PaymentMethodContract) {
            $this->notification->error('payment.errorDuringPaymentExecution', __METHOD__);
            return $this->response->redirectTo('checkout');
        }

        $response = $this->paymentService->sendPaymentRequest(
            $basket,
            $paymentMethod,
            $methodInstance->getTransactionType(),
            $mopId,
            ['birthday' => $this->getDateOfBirth(), 'salutation' => $this->getSalutation()]
        );

        try {
            $this->processResponse($response);
        } catch (\RuntimeException $e) {
            $this->notification->error(
               'payment.errorDuringPaymentExecution',
               __METHOD__,
               ['Message' => $e->getMessage()]
            );
            return $this->response->redirectTo('checkout');
        }

        $this->notification->success('payment.infoPaymentSuccessful', __METHOD__);
        return $this->response->redirectTo('place-order');
    }

    /**
     * Handles a transaction response and returns a success flag.
     * Returns true if the transaction was successful and false if it was not.
     *
     * @test
     *
     * @param array $postResponse
     * @throws \RuntimeException
     */
    public function processResponse($postResponse)
    {
        ksort($postResponse);

        $response = $this->paymentService->handlePaymentResponse(['response' => $postResponse]);
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
                return;
            }
        }

        $errorMsg = 'An error occurred handling the transaction.';

        if (isset($response['response'])) {
            $responseObj = $response['response'];
            $errorMsg    = ($responseObj['PROCESSING.REASON'] ?? '') . ': ' . ($responseObj['PROCESSING.RETURN'] ?? '');
        }

        throw new \RuntimeException($errorMsg);
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
     * @return BaseResponse
     */
    public function emergencyRedirect(): BaseResponse
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
    //</editor-fold>
}
