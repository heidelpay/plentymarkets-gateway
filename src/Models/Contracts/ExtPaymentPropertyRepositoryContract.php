<?php
namespace Heidelpay\Models\Contracts;

interface ExtPaymentPropertyRepositoryContract
{
    /**
     * Lists properties of a property type with the given value. The ID of the property type must be specified.
     * @param int $typeId
     * @param string $value
     * @return array
     */
    public function allByTypeIdAndValue(
        int $typeId,
        string $value
    ):array;
}
