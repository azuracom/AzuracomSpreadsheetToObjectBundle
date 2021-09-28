<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\DecimalTransformer;
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