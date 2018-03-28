<?php

namespace Heidelpay\Migrations;

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
     * CreatePaymentMethods constructor.
     *
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->helper = $paymentHelper;
    }

    /**
     * Creates the payment methods (mops) if they do not exist.
     */
    public function run()
    {
        foreach ($this->helper::getPaymentMethods() as $paymentMethod) {
            $this->helper->createMopIfNotExists($paymentMethod);
        }
    }
}
