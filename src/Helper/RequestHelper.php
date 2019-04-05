<?php
/**
 * Provides for helper methods concerning the handling of requests.
 *
 * @license Use of this software requires acceptance of the License Agreement. See LICENSE file.
 * @copyright Copyright © 2017-present heidelpay GmbH. All rights reserved.
 *
 * @link https://dev.heidelpay.com/plentymarkets
 *
 * @author Simon Gabriel <development@heidelpay.com>
 *
 * @package heidelpay\plentymarkets-gateway\helpers
 */

namespace Heidelpay\Helper;

use Plenty\Plugin\Http\Request;
use RuntimeException;

class RequestHelper
{
    /**
     * Returns the salutation from the post request.
     *
     * @param Request $request
     * @return string
     * @throws RuntimeException
     */
    public function getSalutation(Request $request): string
    {
        if ($request->exists('customer_salutation')) {
            return $request->get('customer_salutation');
        }

        throw new RuntimeException('payment.errorSalutationIsInvalid');
    }

    /**
     * Returns the date of birth from the request.
     *
     * @param Request $request
     * @return string
     * @throws RuntimeException
     */
    public function getDateOfBirth(Request $request): string
    {
        if ($request->exists('customer_dob_day') &&
            $request->exists('customer_dob_month') &&
            $request->exists('customer_dob_year')) {
            return implode(
                '-',
                [
                    $request->get('customer_dob_year'),
                    $request->get('customer_dob_month'),
                    $request->get('customer_dob_day')
                ]
            );
        }

        throw new RuntimeException('payment.errorDateOfBirthIsInvalid');
    }
}
