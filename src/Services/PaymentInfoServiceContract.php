<?php
/**
 * Provides methods to handle payment information such as the bank data the client should transfer the amount to.
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

    /**
     * Adds the payment information as a note to the order if it is an invoice order.
     * It will be translated into the language of the buyer.
     *
     * @param int $orderId
     */
    public function addPaymentInfoToOrder(int $orderId);
}