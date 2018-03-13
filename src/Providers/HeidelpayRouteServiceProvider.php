<?php

namespace Heidelpay\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * heidelpay Route Service Provider
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link http://dev.heidelpay.com/plentymarkets-gateway
 *
 * @author Stephano Vogel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\providers
 */
class HeidelpayRouteServiceProvider extends RouteServiceProvider
{
    /**
     * Register mappings for the routes.
     *
     * @param Router $router
     */
    public function map(Router $router)
    {
        // heidelpay Payment API responses
        $router->get('heidelpay/response', 'Heidelpay\Controllers\ResponseController@emergencyRedirect');
        $router->post('heidelpay/response', 'Heidelpay\Controllers\ResponseController@processResponse');

        // redirects in success or cancellation/failure cases
        $router->get('heidelpay/checkoutSuccess', 'Heidelpay\Controllers\PaymentController@checkoutSuccess');
        $router->get('heidelpay/checkoutCancel', 'Heidelpay\Controllers\PaymentController@checkoutCancel');
    }
}
