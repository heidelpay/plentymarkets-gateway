<?php

namespace Heidelpay\Migrations;

use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Helper\PaymentHelper;

/**
 * CreatePaymentMethods migration class
 *
 * Create the heidelpay payment methods, if they do not exist.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\migrations
 */
class CreatePaymentMethods
{
    /**
     * @var PaymentHelper
     */
    private $helper;
    /**
     * @var MethodConfigContract
     */
    private $methodConfig;

    /**
     * CreatePaymentMethods constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param MethodConfigContract $methodConfig
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        MethodConfigContract $methodConfig
    ) {
        $this->helper = $paymentHelper;
        $this->methodConfig = $methodConfig;
    }

    /**
     * Creates the payment methods (mops) if they do not exist.
     */
    public function run()
    {
        foreach ($this->methodConfig::getPaymentMethods() as $paymentMethod) {
            $this->helper->createMopIfNotExists($paymentMethod);
        }
    }
}
