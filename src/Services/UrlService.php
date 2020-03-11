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

class UrlService implements UrlServiceContract
{
    /**
     * Generates the full URL for a given route.
     *
     * @param string $route
     * @return string
     */
    public function generateURL($route): string
    {
        $responseURL = $this->getDomain() . '/' . $route;
        if (isset($_COOKIE['PluginSetPreview'])) {
            $responseURL = rtrim($responseURL, '/');
            $responseURL .= '?pluginSetPreview=' . $_COOKIE ['PluginSetPreview'];
        }
        return $responseURL;
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
