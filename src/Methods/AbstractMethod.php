<?php

namespace Heidelpay\Methods;

use Heidelpay\Helper\PaymentHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;

/**
 * Abstract Payment Method Class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
abstract class AbstractMethod extends PaymentMethodService implements PaymentMethodContract
{
    const CONFIG_KEY = 'abstract';
    const DEFAULT_NAME = 'Abstract Payment Method';
    const KEY = 'ABSTRACT';

    /**
     * @var PaymentHelper $helper
     */
    protected $helper;

    /**
     * @var BasketRepositoryContract $basketRepository
     */
    protected $basketRepository;

    /**
     * AbstractMethod constructor.
     *
     * @param PaymentHelper          $paymentHelper
     * @param BasketRepositoryContract $basketRepositoryContract
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        BasketRepositoryContract $basketRepositoryContract
    ) {
        $this->helper = $paymentHelper;
        $this->basketRepository = $basketRepositoryContract;
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        // return false if this method is not configured as active.
        if (! $this->helper->getIsActive($this)) {
            return false;
        }

        $basket = $this->basketRepository->load();

        // check the configured minimum cart amount and return false if an amount is configured
        // (which means > 0.00) and the cart amount is below the configured value.
        $minAmount = $this->helper->getMinAmount($this);
        if ($minAmount > 0.00 && $basket->basketAmount < $minAmount) {
            return false;
        }

        // check the configured maximum cart amount and return false if an amount is configured
        // (which means > 0.00) and the cart amount is above the configured value.
        $maxAmount = $this->helper->getMaxAmount($this);
        return !($maxAmount > 0.00 && $basket->basketAmount > $maxAmount);
    }

    /**
     * @inheritdoc
     */
    public function isExpressCheckout(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFee(): float
    {
        return 0.00;
    }

    /**
     * @inheritdoc
     */
    public function isSelectable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isSwitchableTo(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isSwitchableFrom(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getConfigKey(): string
    {
        return static::CONFIG_KEY;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultName(): string
    {
        return static::DEFAULT_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getMethodKey(): string
    {
        return static::KEY;
    }

    /**
     * @inheritdoc
     */
    public static function getPaymentMethodDefaultName(): string
    {
        return static::DEFAULT_NAME;
    }

    /**
     * @inheritdoc
     */
    public static function getPaymentMethodKey(): string
    {
        return static::KEY;
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->helper->getPaymentMethodName($this) ?: $this->getDefaultName();
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): string
    {
        return $this->helper->getMethodIcon($this);
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->helper->getMethodDescription($this);
    }
}
