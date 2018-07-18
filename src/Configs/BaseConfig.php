<?php

namespace Heidelpay\Configs;
use Heidelpay\Constants\Plugin;
use Plenty\Plugin\ConfigRepository;

/**
 * Base class for all configuration classes.
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
class BaseConfig
{
    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * MainConfig constructor.
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        ConfigRepository $configRepository
    ) {
        $this->config = $configRepository;
    }

    /**
     * Return the value of the passed key.
     * @param string $key
     * @return mixed
     */
    protected function get(string $key)
    {
        return $this->config->get($key);
    }

    /**
     * Return true if the parameter exists.
     * @param string $key
     * @return mixed
     */
    protected function has(string $key)
    {
        return $this->config->has($key);
    }

    /**
     * Returns the complete config key (plugin name + config key) for a given key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function getConfigKey(string $key): string
    {
        return Plugin::NAME . '.' . $key;
    }

    /**
     * Converts a string to float replacing comma with decimal point.
     *
     * @param $value
     * @return float
     */
    public function stringToFloat($value): float
    {
        return (float)str_replace(',', '.', $value);
    }
}
