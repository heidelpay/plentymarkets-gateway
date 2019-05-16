<?php
/**
 * Provides for connection to the libraries.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */

namespace Heidelpay\Services;

use Heidelpay\Constants\Plugin;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\DebitCard;
use Heidelpay\Methods\DirectDebit;
use Heidelpay\Methods\Sofort;
use Heidelpay\Methods\InvoiceSecuredB2C;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
class LibService
{
    /**
     * @var LibraryCallContract
     */
    private $libCall;
    /**
     * @var NotificationServiceContract
     */
    private $notification;

    /**
     * LibService constructor.
     *
     * @param LibraryCallContract $libraryCallContract
     * @param NotificationServiceContract $notification
     */
    public function __construct(
        LibraryCallContract $libraryCallContract,
        NotificationServiceContract $notification
    ) {
        $this->libCall = $libraryCallContract;
        $this->notification = $notification;
    }

    //<editor-fold desc="General">
    /**
     * Executes an external library call with the given parameters.
     *
     * @param string $libCall
     * @param array  $params
     * @param string $pluginName
     *
     * @return array
     */
    private function executeLibCall($libCall, array $params, $pluginName = Plugin::NAME): array
    {
        $libName = $pluginName . '::' . $libCall;
        $result = $this->libCall->call($libName, $params);

        $logData = ['LibCall' => $libName, 'Parameters' => $params, 'Result' => $result];
        $this->notification->debug('request.debugLibCallResult', __METHOD__, $logData);

        // if an exception/error occurred when trying to call the external sdk, return
        // the values from the assoc array containing the error details.
        if ($result['error'] ?? false) {
            return [
                'exceptionCode' => $result['error_no'] ?? 500,
                'exceptionMsg' => $result['error_msg'] ?? 'Internal error'
            ];
        }

        return $result;
    }
    //</editor-fold>

    //<editor-fold desc="Response Handlers">
    /**
     * Handles the asynchronous heidelpay POST Response and returns the Response array.
     *
     * @param array $params
     *
     * @return array
     */
    public function handleResponse(array $params): array
    {
        return $this->executeLibCall('handleAsyncResponse', $params);
    }

    /**
     * Handle the heidelpay XML Push Notification and returns the Response array.
     *
     * @param array $params
     *
     * @return array
     */
    public function handlePushNotification(array $params): array
    {
        return $this->executeLibCall('handlePushNotification', $params);
    }
    //</editor-fold>

    //<editor-fold desc="Transaction Requests">

    /**
     * Calls a method depending on the given payment method
     * for sending a transaction request.
     *
     * @param string $paymentMethod
     * @param array  $params
     *
     * @return array
     */
    public function sendTransactionRequest(string $paymentMethod, array $params): array
    {
        switch ($paymentMethod) {
            case CreditCard::class:
                return $this->sendCreditCardTransactionRequest($params);
                break;

            case DebitCard::class:
                return $this->sendDebitCardTransactionRequest($params);
                break;

            case Sofort::class:
                return $this->sendSofortTransactionRequest($params);
                break;

            case InvoiceSecuredB2C::class:
                return $this->sendInvoiceSecuredB2CTransactionRequest($params);
                break;

            case DirectDebit::class:
                return $this->sendDirectDebitTransactionRequest($params);
                break;

            default:
                return [];
        }
    }

    /**
     * Submits a request for a Credit Card transaction.
     *
     * @param array $params
     *
     * @return array
     */
    protected function sendCreditCardTransactionRequest(array $params): array
    {
        return $this->executeLibCall('creditcardTransactionRequest', $params);
    }

    /**
     * Submits a request for a Debit Card transaction.
     *
     * @param array $params
     *
     * @return array
     */
    protected function sendDebitCardTransactionRequest(array $params): array
    {
        return $this->executeLibCall('debitcardTransactionRequest', $params);
    }

    /**
     * Submits a request for a Sofort transaction.
     *
     * @param array $params
     *
     * @return array
     */
    protected function sendSofortTransactionRequest(array $params): array
    {
        return $this->executeLibCall('sofortTransactionRequest', $params);
    }

    /**
     * Submits a request for a Invoice Secured B2C transaction.
     *
     * @param array $params
     *
     * @return array
     */
    protected function sendInvoiceSecuredB2CTransactionRequest(array $params): array
    {
        return $this->executeLibCall('invoiceSecuredB2CTransactionRequest', $params);
    }

    /**
     * Submits a request for a Direct Debit transaction.
     *
     * @param array $params
     *
     * @return array
     */
    protected function sendDirectDebitTransactionRequest(array $params): array
    {
        return $this->executeLibCall('directdebitTransactionRequest', $params);
    }

    //</editor-fold>

    //<editor-fold desc="Basket">
    /**
     * @param array $params
     *
     * @return array
     */
    public function submitBasket(array $params): array
    {
        return $this->executeLibCall('submitBasket', $params);
    }
    //</editor-fold>
}
