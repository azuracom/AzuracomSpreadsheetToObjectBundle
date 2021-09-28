<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;


use Symfony\Component\Form\Exception\TransformationFailedException;

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

        if (!preg_match("#^\d+$#",$stringValue)) {
            throw new TransformationFailedException("Cette valeur n'est pas entier valide");
        }

        return (int) $stringValue;
    }
}
