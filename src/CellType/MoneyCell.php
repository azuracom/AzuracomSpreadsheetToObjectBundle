<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\MoneyTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class MoneyCell extends AbstractCell
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