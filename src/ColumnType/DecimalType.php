<?php

namespace Azuracom\SpreadsheetToObjectBundle\ColumnType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\DecimalTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class DecimalType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new DecimalTransformer();
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        return floatval($newValue) !== floatval($oldValue);
    }
}