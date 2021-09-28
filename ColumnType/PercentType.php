<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\PercentTransformer;


class PercentType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new PercentTransformer();
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        return floatval($newValue) !== floatval($oldValue);
    }
}