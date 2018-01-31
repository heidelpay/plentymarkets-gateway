<?php

namespace Heidelpay\Services;

use Heidelpay\Constants\Plugin;
use Heidelpay\Helper\PaymentHelper;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\ConfigRepository;

/**
 * heidelpay Payment Service class
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present Heidelberger Payment GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.de>
 *
 * @package heidelpay\plentymarkets-gateway
 */
class PaymentService
{
    /**
     * @var string
     */
    private $returnType;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepository;

    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var LibraryCallContract
     */
    private $libCall;

    public function __construct(
        ConfigRepository $configRepository,
        LibraryCallContract $libraryCaller,
        PaymentMethodRepositoryContract $paymentMethodRepository,
        PaymentRepositoryContract $paymentRepository,
        PaymentHelper $paymentHelper
    ) {
        $this->configRepository = $configRepository;
        $this->libCall = $libraryCaller;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentRepository = $paymentRepository;
        $this->paymentHelper = $paymentHelper;
    }

    public function getPaymentMethodContent(Basket $basket): string
    {
        return '';
    }

    /**
     * @param Basket $basket
     * @param $content
     *
     * @return array
     */
    private function prepareRequest(Basket $basket, $content): array
    {
        $requestArray = [];
        $requestArray = array_merge($requestArray, $this->paymentHelper->getHeidelpayAuthenticationConfig());

        // TODO: get channel by payment method

        // TODO: gather data by basket, customer data, etc...

        return $requestArray;
    }
}
