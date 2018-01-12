<?php

namespace Heidelpay\Methods;

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
     * @inheritdoc
     */
    public function isActive(
        ConfigRepository $configRepository,
        BasketRepositoryContract $basketRepositoryContract
    ): bool {
        /** @var bool $isActive */
        $isActive = true;

        if ($configRepository->get($this->helper->getIsActiveKey($this)) === false) {
            return false;
        }

        return $isActive;
    }

    /**
     * @inheritdoc
     */
    public function getConfigKey(): string
    {
        return self::CONFIG_KEY;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultName(): string
    {
        return self::DEFAULT_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMethodKey(): string
    {
        return self::KEY;
    }
}
