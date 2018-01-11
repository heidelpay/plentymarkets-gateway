<?php

namespace Heidelpay\Helper;

use Heidelpay\Methods\PrepaymentPaymentMethod;

/**
 * Helper class for the Prepayment payment method
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\helper
 */
class PrepaymentHelper extends AbstractHelper
{
    /**
     * @inheritdoc
     */
    public function getPaymentKey(): string
    {
        return PrepaymentPaymentMethod::KEY;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethodConfigKey(): string
    {
        return PrepaymentPaymentMethod::CONFIG_KEY;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethodDefaultName(): string
    {
        return PrepaymentPaymentMethod::DEFAULT_NAME;
    }
}
