<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class MoneyTransformer implements DataTransformerInterface
{
    public function transform($intValue)
    {
        if ($intValue === null) {
            return null;
        }

        return (string) ($intValue / 100);
    }
    public function reverseTransform($stringValue)
    {
        if ($stringValue === null) {
            return null;
        }
            
        $intVal = (int) (float) (string) (floatval(str_replace(',','.',$stringValue)) * 100);

        return $intVal;
    }
}
