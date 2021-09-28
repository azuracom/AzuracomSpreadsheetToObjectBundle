<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

class TextType extends AbstractType
{
    public function hasChangedInner($newValue, $oldValue): bool
    {
        return $newValue != $oldValue;
    }
}