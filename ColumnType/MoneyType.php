<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\MoneyTransformer;


class MoneyType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new MoneyTransformer();
    }

    public function hasChangedInner($newValue,$oldValue) :bool
    {
        return (int) $newValue !== (int) $oldValue;
    }
}