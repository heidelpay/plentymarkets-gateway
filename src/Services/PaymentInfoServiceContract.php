<?php
/**
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/${Package}
 */

namespace Heidelpay\Services;

use Plenty\Modules\Order\Models\Order;

interface PaymentInfoServiceContract
{
    /**
     * Returns the payment information for the given order as string if it is an invoice payment type.
     *
     * @param Order $order
     * @param $language
     * @return string
     */
    public function getPaymentInformationString(Order $order, $language): string;
}