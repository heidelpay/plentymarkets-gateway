<?php
/**
 * Description
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/${Package}
 */
namespace Heidelpay\Configs;

use Heidelpay\Constants\Plugin;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

class BaseConfig
{
    use Loggable;

    /**
     * @var ConfigRepository
     */
    private $config;

    /**
     * MainConfig constructor.
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->config = $configRepository;
    }

    /**
     * Return the value of the passed key.
     * @param string $key
     * @return mixed
     */
    protected function get(string $key)
    {
        $value = $this->config->get($key);
        $this->getLogger(__METHOD__)
            ->debug('heidelpay:payment.debugReadConfigKey', ['key' => $key, 'value' => $value]);
        return $value;
    }

    /**
     * Return true if the parameter exists.
     * @param string $key
     * @return mixed
     */
    protected function has(string $key)
    {
        $value = $this->config->has($key);
        $this->getLogger(__METHOD__)
            ->debug('heidelpay:payment.debugHasConfigKey', ['key' => $key, 'value' => $value ? 'true': 'false']);
        return $value;
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
