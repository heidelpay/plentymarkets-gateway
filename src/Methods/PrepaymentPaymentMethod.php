<?php

namespace Heidelpay\Methods;

use Heidelpay\Helper\PrepaymentHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Plugin\ConfigRepository;

/**
 * heidelpay Prepayment Payment Method
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
class PrepaymentPaymentMethod extends AbstractPaymentMethod
{
    const CONFIG_KEY = 'prepayment';
    const KEY = 'PREPAYMENT';
    const DEFAULT_NAME = 'Prepayment';

    /**
     * CreditCardPaymentMethod constructor.
     *
     * @param PrepaymentHelper $paymentHelper
     */
    public function __construct(PrepaymentHelper $paymentHelper)
    {
        $this->helper = $paymentHelper;
    }

    /**
     * @inheritdoc
     */
    public function isActive(
        ConfigRepository $configRepository,
        BasketRepositoryContract $basketRepositoryContract
    ): bool {
        /** @var bool $isActive */
        $isActive = true;

        if ($configRepository->get($this->helper->getIsActiveKey()) === false) {
            return false;
        }

        return $isActive;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultName(): string
    {
        return self::DEFAULT_NAME;
    }
}
