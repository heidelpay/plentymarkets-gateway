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

class ArraySerializerService
{
    const SERIALIZER_PAIR_DELIMITER = '; ';
    const SERIALIZER_KEY_VALUE_DELIMITER = ': ';

    /**
     * @param array $bookingTextArray
     * @return string
     */
    public function serializeKeyValue(array $bookingTextArray): string
    {
        return implode(self::SERIALIZER_PAIR_DELIMITER, array_map(
            function ($value, $key) {
                return $key . self::SERIALIZER_KEY_VALUE_DELIMITER . $value;
            },
            $bookingTextArray,
            array_keys($bookingTextArray)
        ));
    }

    /**
     * Creates an array from a string serialized with the serializeKeyValue method.
     *
     * @param string $bookingText
     * @return array
     */
    public function deserializeKeyValue($bookingText): array
    {
        $resultArray = [];
        foreach (explode(self::SERIALIZER_PAIR_DELIMITER, $bookingText) as $element) {
            list($key, $value) = explode(self::SERIALIZER_KEY_VALUE_DELIMITER, $element);
            $resultArray[$key] = $value;
        }
        return $resultArray;
    }
}
