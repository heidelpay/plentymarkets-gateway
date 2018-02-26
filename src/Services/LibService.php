<?php

namespace Heidelpay\Services;

use Heidelpay\Constants\Plugin;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

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
    public function handleResponse($params): array
    {
        return $this->executeLibCall('handleResponse', $params);
    }

    /**
     * Submits a request for a PayPal transaction.
     *
     * @param $params
     *
     * @return array
     */
    public function sendPayPalTransactionRequest($params): array
    {
        return $this->executeLibCall('payPalTransactionRequest', $params);
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
        return $this->libCall->call($pluginName . '::' . $libCall, $params);
    }
}