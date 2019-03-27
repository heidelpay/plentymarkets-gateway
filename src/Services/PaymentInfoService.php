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

use Heidelpay\Helper\PaymentHelper;
use Plenty\Modules\Order\Models\Order;

class PaymentInfoService implements PaymentInfoServiceContract
{
    /**
     * @var NotificationServiceContract
     */
    private $notificationService;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * PaymentInformationService constructor.
     * @param NotificationServiceContract $notificationService
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        NotificationServiceContract $notificationService,
        PaymentHelper $paymentHelper
    ) {
        $this->notificationService = $notificationService;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentInformationString(Order $order, $language): string
    {
        $paymentDetails = $this->paymentHelper->getPaymentDetailsForOrder($order);

        $adviceParts                = [
            $this->notificationService->getTranslation('Heidelpay::template.pleaseTransferTheTotalTo', [], $language),
            $this->notificationService->getTranslation('Heidelpay::template.accountIban', [], $language) . ': ' .
            $paymentDetails['accountIBAN'],
            $this->notificationService->getTranslation('Heidelpay::template.accountBic', [], $language) . ': ' .
            $paymentDetails['accountBIC'],
            $this->notificationService->getTranslation('Heidelpay::template.accountHolder', [], $language) . ': ' .
            $paymentDetails['accountHolder'],
            $this->notificationService->getTranslation('Heidelpay::template.accountUsage', [], $language) . ': ' .
            $paymentDetails['accountUsage']
        ];
        return implode(PHP_EOL, $adviceParts);
    }
}
