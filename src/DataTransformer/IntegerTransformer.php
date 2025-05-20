<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;

class IntegerTransformer implements DataTransformerInterface
{

    public function transform($intValue)
    {
        return $intValue;
    }

    public function reverseTransform($stringValue)
    {
        if ($stringValue === null) {
            return null;
        }

        if (!preg_match("#^(-)?\d+$#",$stringValue)) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.integer");
        }

        return (int) $stringValue;
    }
}
