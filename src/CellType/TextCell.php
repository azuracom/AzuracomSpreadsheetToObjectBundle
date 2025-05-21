<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

class TextCell extends AbstractCell
{
    public function hasChangedInner($newValue, $oldValue): bool
    {
        return $newValue != $oldValue;
    }
}