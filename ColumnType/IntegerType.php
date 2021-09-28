<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\IntegerTransformer;


class IntegerType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new IntegerTransformer();
    }

    public function hasChangedInner($newValue,$oldValue) :bool
    {
        return (int) $newValue !== (int) $oldValue;
    }
}