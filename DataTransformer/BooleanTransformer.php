<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObject\Exception\TransformationFailedException;

class BooleanTransformer implements DataTransformerInterface
{
    public function transform($boolvalue)
    {
        return $boolvalue ? 1 : 0;
    }

    public function reverseTransform($stringValue)
     {
        if ($stringValue === null) {
            return null;
        }
        if ($stringValue != 1 && $stringValue != 0) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.boolean");
        }

        return $stringValue == 1 ? true : false;
    }
}