<?php

namespace Heidelpay\Methods;

use Heidelpay\Constants\DescriptionTypes;
use Heidelpay\Helper\HeidelpayHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\ConfigRepository;

/**
 * Abstract Payment Method Class
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
abstract class AbstractPaymentMethod extends PaymentMethodService
{
    const CONFIG_KEY = 'abstract';
    const DEFAULT_NAME = 'Abstract Payment Method';
    const KEY = 'ABSTRACT';

    /**
     * @var HeidelpayHelper $helper
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
     * AbstractPaymentMethod constructor.
     *
     * @param HeidelpayHelper          $paymentHelper
     * @param ConfigRepository         $configRepository
     * @param BasketRepositoryContract $basketRepositoryContract
     */
    public function __construct(
        HeidelpayHelper $paymentHelper,
        ConfigRepository $configRepository,
        BasketRepositoryContract $basketRepositoryContract
    ) {
        $this->helper = $paymentHelper;
        $this->configRepository = $configRepository;
        $this->basketRepository = $basketRepositoryContract;
    }

    /**
     * Returns if the payment method is active
     * and can be used by the customer.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        /** @var Basket $basket */
        $basket = $this->basketRepository->load();

        // return false if this method is not configured as active.
        if ($this->configRepository->get($this->helper->getIsActiveKey($this)) === false) {
            return false;
        }

        // check the configured minimum cart amount and return false if an amount is configured
        // (which means > 0.00) and the cart amount is below the configured value.
        $minAmount = $this->configRepository->get($this->helper->getMinAmountKey($this));
        if ($minAmount !== null && $minAmount > 0.00 && $basket->basketAmount < $minAmount) {
            return false;
        }

        // check the configured maximum cart amount and return false if an amount is configured
        // (which means > 0.00) and the cart amount is above the configured value.
        $maxAmount = $this->configRepository->get($this->helper->getMaxAmountKey($this));
        return !($maxAmount !== null && $maxAmount > 0.00 && $basket->basketAmount > $maxAmount);
    }

    /**
     * Returns the config key for the payment method.
     *
     * @return string
     */
    public function getConfigKey(): string
    {
        return static::CONFIG_KEY;
    }

    /**
     * Returns a default display name for the payment method.
     *
     * @return string
     */
    public function getDefaultName(): string
    {
        return static::DEFAULT_NAME;
    }

    /**
     * Returns the key for the payment method.
     *
     * @return string
     */
    public function getMethodKey(): string
    {
        return static::KEY;
    }

    /**
     * Returns the config key for the payment method (static).
     *
     * @return string
     */
    public static function getPaymentMethodConfigKey(): string
    {
        return static::CONFIG_KEY;
    }

    /**
     * Returns the default display name for the payment method (static).
     *
     * @return string
     */
    public static function getPaymentMethodDefaultName(): string
    {
        return static::DEFAULT_NAME;
    }

    /**
     * Returns the key for the payment method (static).
     *
     * @return string
     */
    public static function getPaymentMethodKey(): string
    {
        return static::KEY;
    }

    /**
     * Returns the configured payment method display name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->configRepository->get($this->helper->getDisplayNameKey($this)) ?: $this->getDefaultName();
    }

    /**
     * Returns the configured icon logo, if logo usage is enabled for this payment method.
     *
     * @return string
     */
    public function getIcon(): string
    {
        if ($this->configRepository->get($this->helper->getUseIconKey($this)) === false) {
            return '';
        }

        return $this->configRepository->get($this->helper->getIconUrlKey($this)) ?: '';
    }

    /**
     * Returns the configured payment method description
     *
     * @return string
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
