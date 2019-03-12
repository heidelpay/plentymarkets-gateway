<?php
/**
 * Container file to show additional payment information on the order confirmation page.
 * E.g. for invoice payment type.
 *
 * @license Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 * @copyright Copyright © 2016-present heidelpay GmbH. All rights reserved.
 *
 * @link  http://dev.heidelpay.com/
 *
 * @author  Simon Gabriel <development@heidelpay.de>
 *
 * @package  heidelpay/plentymarkets-gateway/containers
 */

namespace Heidelpay\Containers;

use Plenty\Plugin\Templates\Twig;

class AdditionalPaymentInformationContainer
{
    public function call(Twig $twig):string
    {
        return $twig->render('Heidelpay::content.AdditionalPaymentInformation');
    }
}