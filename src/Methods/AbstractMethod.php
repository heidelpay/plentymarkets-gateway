<?php

namespace Heidelpay\Methods;

use Heidelpay\Constants\DescriptionTypes;
use Heidelpay\Helper\PaymentHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\ConfigRepository;

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
     * @var ConfigRepository $configRepository
     */
    protected $configRepository;

    /**
     * @var BasketRepositoryContract $basketRepository
     */
    protected $basketRepository;

    /**
     * AbstractMethod constructor.
     *
     * @param PaymentHelper          $paymentHelper
     * @param ConfigRepository         $configRepository
     * @param BasketRepositoryContract $basketRepositoryContract
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        ConfigRepository $configRepository,
        BasketRepositoryContract $basketRepositoryContract
    ) {
        $this->helper = $paymentHelper;
        $this->configRepository = $configRepository;
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

        /** @var Basket $basket */
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
        return $this->configRepository->get($this->helper->getDisplayNameKey($this)) ?: $this->getDefaultName();
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
        $descriptionType = $this->configRepository->get($this->helper->getDescriptionTypeKey($this));

        if ($descriptionType === DescriptionTypes::INTERNAL) {
            return $this->configRepository->get($this->helper->getDescriptionKey($this, true));
        }

        if ($descriptionType === DescriptionTypes::EXTERNAL) {
            return $this->configRepository->get($this->helper->getDescriptionKey($this));
        }

        // in case of DescriptionTypes::NONE
        return '';
    }
}
