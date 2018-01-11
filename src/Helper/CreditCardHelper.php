<?php

namespace Heidelpay\Helper;

use Heidelpay\Methods\CreditCardPaymentMethod;

/**
 * heidelpay Credit Card Helper
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
class CreditCardHelper extends AbstractHelper
{
    /**
     * @inheritdoc
     */
    public function getPaymentKey(): string
    {
        return CreditCardPaymentMethod::KEY;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethodConfigKey(): string
    {
        return CreditCardPaymentMethod::CONFIG_KEY;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethodDefaultName(): string
    {
        return CreditCardPaymentMethod::DEFAULT_NAME;
    }
}
