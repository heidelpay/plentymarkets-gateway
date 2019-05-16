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

use Heidelpay\Helper\CommentHelper;
use Heidelpay\Helper\OrderModelHelper;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Methods\AbstractMethod;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;

class PaymentInfoService implements PaymentInfoServiceContract
{
    /** @var NotificationServiceContract */
    private $notification;

    /** @var PaymentHelper */
    private $paymentHelper;

    /** @var OrderRepositoryContract */
    private $orderRepository;

    /** @var OrderModelHelper */
    private $modelHelper;

    /** @var CommentHelper */
    private $commentHelper;

    /**
     * PaymentInformationService constructor.
     * @param NotificationServiceContract $notificationService
     * @param PaymentHelper $paymentHelper
     * @param OrderRepositoryContract $orderRepository
     * @param OrderModelHelper $modelHelper
     * @param CommentHelper $commentHelper
     */
    public function __construct(
        NotificationServiceContract $notificationService,
        PaymentHelper $paymentHelper,
        OrderRepositoryContract $orderRepository,
        OrderModelHelper $modelHelper,
        CommentHelper $commentHelper
    ) {
        $this->notification    = $notificationService;
        $this->paymentHelper   = $paymentHelper;
        $this->orderRepository = $orderRepository;
        $this->modelHelper     = $modelHelper;
        $this->commentHelper   = $commentHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentInformationString(Order $order, $language, $glue = PHP_EOL): string
    {
        $details = $this->paymentHelper->getPaymentDetailsForOrder($order);
        $adviceParts = [
            $this->notification->translate('template.pleaseTransferTheTotalTo', [], $language),
            $this->notification->translate('template.accountIban', [], $language) . ': ' . $details['accountIBAN'],
            $this->notification->translate('template.accountBic', [], $language) . ': ' . $details['accountBIC'],
            $this->notification->translate('template.accountHolder', [], $language) . ': ' . $details['accountHolder'],
            $this->notification->translate('template.accountUsage', [], $language) . ': ' . $details['accountUsage']
        ];
        return implode($glue, $adviceParts);
    }

    /**
     * {@inheritDoc}
     */
    public function addPaymentInfoToOrder(int $orderId)
    {
        $order = $this->orderRepository->findOrderById($orderId);
        $instance = $this->paymentHelper->getPaymentMethodInstanceByMopId($order->methodOfPaymentId);
        if (!$instance instanceof AbstractMethod || !$instance->renderInvoiceData()) {
            return;
        }

        $language = $this->modelHelper->getLanguage($order);
        $commentText = $this->getPaymentInformationString($order, $language, '</br>');
        $this->commentHelper->createOrderComment($orderId, $commentText);

    }
}
