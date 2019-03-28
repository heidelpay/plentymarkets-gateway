<?php

namespace Heidelpay\Methods;

use Heidelpay\Configs\MethodConfigContract;
use Heidelpay\Helper\PaymentHelper;
use Heidelpay\Services\BasketServiceContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\Application;

/**
 * Abstract Payment Method Class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\payment-methods
 */
abstract class AbstractMethod extends PaymentMethodService implements PaymentMethodContract
{
    const CONFIG_KEY = 'abstract';
    const DEFAULT_NAME = 'Abstract Payment Method';
    const KEY = 'ABSTRACT';
    const DEFAULT_ICON_PATH = '/images/logos/default_payment_icon.png';
    const TRANSACTION_TYPE = '';
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL;
    const INITIALIZE_PAYMENT = true;
    const FORM_TEMPLATE = '';
    const NEEDS_CUSTOMER_INPUT = true;
    const NEEDS_BASKET = false;
    const RENDER_INVOICE_DATA = false;
    const B2C_ONLY = false;
    const COUNTRY_RESTRICTION = [];
    const ADDRESSES_MUST_MATCH = false;

    /**
     * @var PaymentHelper $helper
     */
    protected $helper;

    /**
     * @var MethodConfigContract
     */
    private $config;
    /**
     * @var BasketServiceContract
     */
    private $basketService;

    /**
     * AbstractMethod constructor.
     *
     * @param PaymentHelper $paymentHelper
     * @param MethodConfigContract $config
     * @param BasketServiceContract $basketService
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        MethodConfigContract $config,
        BasketServiceContract $basketService
    ) {
        $this->helper = $paymentHelper;
        $this->config = $config;
        $this->basketService = $basketService;
    }

    /**
     * @inheritdoc
     */
    public function isActive(): bool
    {
        // return false if this method is not configured as active.
        if (! $this->config->isActive($this)) {
            return false;
        }

        $basket = $this->basketService->getBasket();

        // check the configured minimum cart amount and return false if an amount is configured
        // (which means > 0.00) and the cart amount is below the configured value.
        $minAmount = $this->config->getMinAmount($this);
        if ($minAmount > 0.00 && $basket->basketAmount < $minAmount) {
            return false;
        }

        // check the configured maximum cart amount and return false if an amount is configured
        // (which means > 0.00) and the cart amount is above the configured value.
        $maxAmount = $this->config->getMaxAmount($this);
        if ($maxAmount > 0.00 && $basket->basketAmount > $maxAmount) {
            return false;
        }

        // enable the payment method only if it is enabled for the current transaction (B2C||B2B)
        if ($this->isB2cOnly() && $this->basketService->isBasketB2B()) {
            return false;
        }

        // enable the payment method only if it is allowed for the given billing country
        $countryRestrictions = $this->getCountryRestrictions();
        if (!empty($countryRestrictions) &&
            !\in_array($this->basketService->getBillingCountryCode(), $countryRestrictions, true)) {
                return false;
        }

        return true;
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
    public static function getConfigKey(): string
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
        return $this->config->getPaymentMethodName($this) ?: $this->getDefaultName();
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): string
    {
        $iconPath = $this->config->getIcon($this);
        if (empty($iconPath)) {
            /** @var Application */
            $app = pluginApp(Application::class);
            $iconPath = $app->getUrlPath('heidelpay'). static::DEFAULT_ICON_PATH;
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->config->getMethodDescription($this);
    }

    /**
     * {@inheritDoc}
     * @throws \RuntimeException
     */
    public function getTransactionType(): string
    {
        if (!empty(static::TRANSACTION_TYPE)) {
            return static::TRANSACTION_TYPE;
        }

        if (!$this->config->hasTransactionType($this)) {
            throw new \RuntimeException('payment.errorTransactionTypeUndefined');
        }
        return $this->config->getTransactionType($this);
    }

    /**
     * Returns the type of the result returned by the payment method initialization.
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return static::RETURN_TYPE;
    }

    /**
     * Returns true if the payment has to be initialized with transaction (i.e. to fetch redirect url).
     *
     * @return bool
     */
    public function hasToBeInitialized(): bool
    {
        return static::INITIALIZE_PAYMENT;
    }

    /**
     * Returns the template of the payment form.
     *
     * @return string
     */
    public function getFormTemplate(): string
    {
        return static::FORM_TEMPLATE;
    }

    /**
     * Returns true if the customer has to be redirected to enter additional information (e.g. 3D-secure, sofort, etc.).
     * This determines whether a synchronous or asynchronous request is performed.
     *
     * @return bool
     */
    public function needsCustomerInput(): bool
    {
        return static::NEEDS_CUSTOMER_INPUT;
    }

    /**
     * {@inheritDoc}
     */
    public function needsBasket(): bool
    {
        return static::NEEDS_BASKET;
    }

    /**
     * {@inheritDoc}
     */
    public function renderInvoiceData(): bool
    {
        return static::RENDER_INVOICE_DATA;
    }

    /**
     * {@inheritDoc}
     */
    public function isB2cOnly(): bool
    {
        return static::B2C_ONLY;
    }

    /**
     * {@inheritDoc}
     */
    public function getCountryRestrictions(): array
    {
        return static::COUNTRY_RESTRICTION;
    }

    /**
     * {@inheritDoc}
     */
    public function needsMatchingAddresses(): bool
    {
        return static::ADDRESSES_MUST_MATCH;
    }
}
