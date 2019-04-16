<?php
/**
 * Provides for helper methods concerning the validation data.
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

use DateTime;
use Heidelpay\Constants\Salutation;
use RuntimeException;
use function in_array;

class ValidationHelper
{
    /**
     * Throws exception if the given salutation is not in the array of allowed Salutations.
     *
     * @param $salutation
     * @param array $allowedSalutations
     *
     * @throws RuntimeException
     */
    public function validateSalutation($salutation, array $allowedSalutations = [Salutation::MR, Salutation::MRS])
    {
        if (!in_array($salutation, $allowedSalutations, true)) {
            throw new RuntimeException('payment.errorSalutationIsInvalid');
        }
    }

    /**
     * Throws exception if the given birthdate shows the customer is not of legal age.
     *
     * @param  DateTime $dob
     * @throws RuntimeException
     */
    public function validateLegalAge(DateTime $dob)
    {
        if(time() < strtotime('+18 years', $dob->getTimestamp()))  {
            throw new RuntimeException('payment.errorUnder18');
        }
    }
}
