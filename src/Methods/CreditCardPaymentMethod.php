<?php

namespace Heidelpay\Methods;

use Heidelpay\Helper\CreditCardHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Plugin\ConfigRepository;

/**
 * heidelpay Credit Card Payment Method
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
class CreditCardPaymentMethod extends AbstractPaymentMethod
{
    const CONFIG_KEY = 'creditcard';
    const KEY = 'CREDIT_CARD';
    const DEFAULT_NAME = 'Credit Card';

    /**
     * CreditCardPaymentMethod constructor.
     *
     * @param CreditCardHelper $creditCardHelper
     */
    public function __construct(CreditCardHelper $creditCardHelper)
    {
        $this->helper = $creditCardHelper;
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
