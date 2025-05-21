<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\PercentTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class PercentCell extends AbstractCell
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