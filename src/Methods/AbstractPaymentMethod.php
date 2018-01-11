<?php

namespace Heidelpay\Methods;

use Heidelpay\Constants\DescriptionTypes;
use Heidelpay\Helper\AbstractHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
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
    /**
     * @var AbstractHelper $helper
     */
    protected $helper;

    /**
     * Returns if the payment method is active
     * and can be used by the customer.
     *
     * @param ConfigRepository         $configRepository
     * @param BasketRepositoryContract $basketRepositoryContract
     *
     * @return bool
     */
    abstract public function isActive(
        ConfigRepository $configRepository,
        BasketRepositoryContract $basketRepositoryContract
    ): bool;

    /**
     * Returns a default display name for the payment method.
     *
     * @return string
     */
    abstract public function getDefaultName(): string;

    /**
     * Returns the configured payment method display name.
     *
     * @param ConfigRepository $configRepository
     *
     * @return string
     */
    public function getName(ConfigRepository $configRepository): string
    {
        return $configRepository->get($this->helper->getDisplayNameKey()) ?: $this->getDefaultName();
    }

    /**
     * Returns the configured icon logo, if logo usage is enabled for this payment method.
     *
     * @param ConfigRepository $configRepository
     *
     * @return string
     */
    public function getIcon(ConfigRepository $configRepository): string
    {
        if ($configRepository->get($this->helper->getUseIconKey()) === false) {
            return '';
        }

        return $configRepository->get($this->helper->getIconUrlKey()) ?: '';
    }

    /**
     * Returns the configured payment method description
     *
     * @param ConfigRepository $configRepository
     *
     * @return string
     */
    public function getDescription(ConfigRepository $configRepository): string
    {
        $descriptionType = $configRepository->get($this->helper->getDescriptionTypeKey());

        if ($descriptionType === DescriptionTypes::INTERNAL) {
            return $configRepository->get($this->helper->getDescriptionKey(true));
        }

        if ($descriptionType === DescriptionTypes::EXTERNAL) {
            return $configRepository->get($this->helper->getDescriptionKey());
        }

        // in case of DescriptionTypes::NONE
        return '';
    }
}
