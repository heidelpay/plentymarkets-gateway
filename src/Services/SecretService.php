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
namespace Heidelpay\Services;

use Heidelpay\Configs\MainConfigContract;
use Heidelpay\Exceptions\SecurityHashInvalidException;

class SecretService
{
    /**
     * @var MainConfigContract
     */
    private $config;

    /**
     * SecretService constructor.
     * @param MainConfigContract $config
     */
    public function __construct(
        MainConfigContract $config
    ) {
        $this->config = $config;
    }

    /**
     * Creates a hash based on the configured secret and the transactionId.
     *
     * @param $value
     * @return string
     */
    public function getSecretHash($value): string
    {
        $secretKey = $this->config->getSecretKey();

        if ($secretKey === '') {
            throw new \RuntimeException('general.errorSecretKeyIsNotConfigured');
        }

        return hash('sha512', $value . $secretKey);
    }

    /**
     * Validates a secretHash.
     *
     * @param $value
     * @param $hash
     * @return bool
     * @throws SecurityHashInvalidException
     */
    public function verifySecretHash($value, $hash): bool
    {
        $referenceHash = $this->getSecretHash($value);

        if ($referenceHash !== $hash) {
            throw new SecurityHashInvalidException('general.errorSecurityHashInvalid');
        }

        return true;
    }
}
