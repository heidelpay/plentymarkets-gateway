<?php
/**
 * Provides URL generation capabilities.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay\plentymarkets-gateway\services
 */
namespace Heidelpay\Services;

use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Plugin\Log\Loggable;

class UrlService implements UrlServiceContract
{
    use Loggable;

    /**
     * Generates the full URL for a given route.
     *
     * @param string $route
     * @return string
     */
    public function generateURL($route): string
    {
        $responseUrl = $this->getDomain() . '/' . $route;
        if (isset($_COOKIE['PluginSetPreview'])) {

            $responseUrlTrimmed = rtrim($responseUrl, '/');
            $this->getLogger(self::class)->error('responseUrl', ['responseURL' => $responseUrl, 'responseUrlTrimmed' => $responseUrlTrimmed]);
            $responseUrl = $responseUrlTrimmed . '?pluginSetPreview=' . $_COOKIE ['PluginSetPreview'];
        }
        return $responseUrl;
    }

    /**
     * Returns the domain of the shop.
     *
     * @return string
     */
    public function getDomain(): string
    {
        /** @var WebstoreHelper $webstoreHelper */
        $webstoreHelper = pluginApp(WebstoreHelper::class);

        return $webstoreHelper->getCurrentWebstoreConfiguration()->domainSsl;
    }
}
