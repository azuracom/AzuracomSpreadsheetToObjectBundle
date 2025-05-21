<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\IntegerTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class IntegerCell extends AbstractCell
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