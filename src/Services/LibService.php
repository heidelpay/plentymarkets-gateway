<?php

namespace Heidelpay\Services;

use Heidelpay\Constants\Plugin;
use Heidelpay\Methods\CreditCard;
use Heidelpay\Methods\PayPal;
use Heidelpay\Methods\Prepayment;
use Heidelpay\Methods\Sofort;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Log\Loggable;

/**
 * heidelpay Lib Service class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\services
 */
class LibService
{
    use Loggable;

    /**
     * @var LibraryCallContract
     */
    private $libCall;

    /**
     * LibService constructor.
     *
     * @param LibraryCallContract $libraryCallContract
     */
    public function __construct(LibraryCallContract $libraryCallContract)
    {
        $this->libCall = $libraryCallContract;
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function handleResponse(array $params): array
    {
        return $this->executeLibCall('handleResponse', $params);
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
     * Submits a request for a PayPal transaction.
     *
     * @param $params
     *
     * @return array
     */
    protected function sendPayPalTransactionRequest(array $params): array
    {
        return $this->executeLibCall('paypalTransactionRequest', $params);
    }

    /**
     * Submits a request for a Prepayment transaction.
     *
     * @param $params
     *
     * @return array
     */
    protected function sendPrepaymentTransactionRequest(array $params): array
    {
        return $this->executeLibCall('prepaymentTransactionRequest', $params);
    }

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

            case Sofort::class:
                return $this->sendSofortTransactionRequest($params);
                break;

            case PayPal::class:
                return $this->sendPayPalTransactionRequest($params);
                break;

            case Prepayment::class:
                return $this->sendPrepaymentTransactionRequest($params);
                break;

            default:
                return [];
        }
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function submitBasket(array $params): array
    {
        return $this->executeLibCall('submitBasket', $params);
    }

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
        $result = $this->libCall->call($pluginName . '::' . $libCall, $params);

        $this->getLogger(__METHOD__)->error('LibCall result', [
            'result' => $result
        ]);

        // if an exception/error occured when trying to call the external sdk,
        // an object will be returned. convert it to an array to work with it.
        if ($result['error'] ?? false) {
            return [
                'exceptionCode' => $result['error_no'] ?? 500,
                'exceptionMsg' => $result['error_msg'] ?? 'Internal error',
            ];
        }

        return $result;
    }
}