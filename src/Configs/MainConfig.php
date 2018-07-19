<?php

namespace Heidelpay\Configs;
use Heidelpay\Constants\Configuration;
use Heidelpay\Constants\TransactionMode;

/**
 * Allows accessing heidelpay main configuration parameters.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay\plentymarkets-gateway\configuration
 */
class MainConfig extends BaseConfig implements MainConfigContract
{
    /**
     * {@inheritDoc}
     */
    public function getSenderId(): string
    {
        return $this->get($this->getConfigKey(Configuration::KEY_AUTH_SENDER_ID));
    }

    /**
     * {@inheritDoc}
     */
    public function getUserLogin(): string
    {
        return $this->get($this->getConfigKey(Configuration::KEY_AUTH_LOGIN));
    }

    /**
     * {@inheritDoc}
     */
    public function getUserPassword(): string
    {
        return $this->get($this->getConfigKey(Configuration::KEY_AUTH_PASSWORD));
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironment(): string
    {
        return $this->isInSandboxMode() ? TransactionMode::CONNECTOR_TEST : TransactionMode::LIVE;
    }

    /**
     * {@inheritDoc}
     */
    public function getSecretKey(): string
    {
        return $this->get($this->getConfigKey(Configuration::KEY_SECRET));
    }

    /**
     * {@inheritDoc}
     */
    public function isInSandboxMode(): bool
    {
        $mode = (int)$this->get($this->getConfigKey(Configuration::KEY_ENVIRONMENT));
        return $mode === Configuration::VALUE_ENVIRONMENT_CONNECTOR_TEST;
    }

    /**
     * {@inheritDoc}
     */
    public function isInLiveMode(): bool
    {
        return !$this->isInSandboxMode();
    }
}
