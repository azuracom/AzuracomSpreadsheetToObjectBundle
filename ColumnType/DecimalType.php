<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\DecimalTransformer;


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