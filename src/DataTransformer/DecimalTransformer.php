<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;


use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;

class DecimalTransformer implements DataTransformerInterface
{

    public function transform(mixed $decimalValue): mixed
    {
        return $decimalValue;
    }

    public function reverseTransform(mixed $stringValue): mixed
    {
        if ($stringValue === null) {
            return null;
        }        

        if (!preg_match("#^(-)?\d+((\.|,)\d+)?$#",$stringValue)) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.decimal");
        }

        $value = floatval(str_replace(',','.',$stringValue));
        return $value;
    }
}
