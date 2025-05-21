<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\DecimalTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class DecimalCell extends AbstractCell
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