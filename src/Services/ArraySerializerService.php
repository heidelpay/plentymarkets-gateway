<?php

namespace Heidelpay\Services;

/**
 * This class serializes a given array to a string and vice versa.
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
class ArraySerializerService
{
    const SERIALIZER_PAIR_DELIMITER = '; ';
    const SERIALIZER_KEY_VALUE_DELIMITER = ': ';

    /**
     * @param array $arrayToSerialize
     * @return string
     */
    public function serializeKeyValue(array $arrayToSerialize): string
    {
        return implode(self::SERIALIZER_PAIR_DELIMITER, array_map(
            static function ($value, $key) {
                return $key . self::SERIALIZER_KEY_VALUE_DELIMITER . $value;
            },
            $arrayToSerialize,
            array_keys($arrayToSerialize)
        ));
    }

    /**
     * Creates an array from a string serialized with the serializeKeyValue method.
     *
     * @param string $serializedString
     * @return array
     */
    public function deserializeKeyValue($serializedString): array
    {
        $resultArray = [];
        foreach (explode(self::SERIALIZER_PAIR_DELIMITER, $serializedString) as $element) {
            list($key, $value) = explode(self::SERIALIZER_KEY_VALUE_DELIMITER, $element);
            $resultArray[$key] = $value;
        }
        return $resultArray;
    }
}
