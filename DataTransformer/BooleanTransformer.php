<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

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
            throw new TransformationFailedException("Valeurs acceptées: 0 ou 1");
        }

        return $stringValue == 1 ? true : false;
    }
}