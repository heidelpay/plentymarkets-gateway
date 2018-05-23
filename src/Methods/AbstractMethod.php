<?php

namespace Heidelpay\Methods;

use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Helper\PaymentHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\Log\Loggable;

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
    use Loggable;

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
     * @var MethodConfigContract
     */
    private $config;

    /**
     * AbstractMethod constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param BasketRepositoryContract $basketRepository
     * @param MethodConfigContract $config
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        BasketRepositoryContract $basketRepository,
        MethodConfigContract $config
    ) {
        $this->helper = $paymentHelper;
        $this->basketRepository = $basketRepository;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        // return false if this method is not configured as active.
        $isActive = $this->config->isActive($this);
        $basket = $this->basketRepository->load();

        if ($isActive) {
            // check the configured minimum cart amount and return false if an amount is configured
            // (which means > 0.00) and the cart amount is below the configured value.
            $minAmount = $this->config->getMinAmount($this);
            if ($minAmount > 0.00 && $basket->basketAmount < $minAmount) {
                $isActive = false;
            }
        }

        if ($isActive) {
            // check the configured maximum cart amount and return false if an amount is configured
            // (which means > 0.00) and the cart amount is above the configured value.
            $maxAmount = $this->config->getMaxAmount($this);

            if ($maxAmount > 0.00 && $basket->basketAmount > $maxAmount) {
                $isActive = false;
            }
        }


        $this->getLogger(__METHOD__)->error(\get_class($this) . '(' . $isActive . ')');

        return $isActive;
    }

    /**
     * @inheritdoc
     */
    public function isExpressCheckout(): bool
    {
        $this->getLogger(__METHOD__)->error('');
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFee(): float
    {
        $this->getLogger(__METHOD__)->error('');
        return 0.00;
    }

    /**
     * @inheritdoc
     */
    public function isSelectable(): bool
    {
        $this->getLogger(__METHOD__)->error('');
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isSwitchableTo(): bool
    {
        $this->getLogger(__METHOD__)->error('');
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isSwitchableFrom(): bool
    {
        $this->getLogger(__METHOD__)->error('');
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
        $this->getLogger(__METHOD__)->error('');
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
        $this->getLogger(__METHOD__)->error('');
        return $this->config->getPaymentMethodName($this) ?: $this->getDefaultName();
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): string
    {
        $this->getLogger(__METHOD__)->error('');
        return $this->config->getMethodIcon($this);
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        $this->getLogger(__METHOD__)->error('');
        return $this->config->getMethodDescription($this);
    }
}
