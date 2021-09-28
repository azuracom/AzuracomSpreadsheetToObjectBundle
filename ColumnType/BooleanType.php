<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\BooleanTransformer;


class BooleanType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new BooleanTransformer();
    }

    public function hasChangedInner($newValue,$oldValue) :bool
    {
        return (int) $newValue !== (int) $oldValue;
    }
}