<?php

namespace Heidelpay\Services;

use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;

/**
 * heidelpay Session Storage Service class
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
class SessionStorageService
{
    /**
     * @var FrontendShippingCountryChanged
     */
    private $sessionStorage;

    /**
     * SessionStorageService constructor.
     *
     * @param FrontendSessionStorageFactoryContract $sessionStorageFactory
     */
    public function __construct(FrontendSessionStorageFactoryContract $sessionStorageFactory)
    {
        $this->sessionStorage = $sessionStorageFactory;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getSessionStorageValue(string $key): mixed
    {
        return $this->sessionStorage->getPlugin()->getValue($key);
    }

    private function setSessionStorageValue(string $key, $value)
    {
        $this->sessionStorage->getPlugin()->setValue($key, $value);
    }
}
