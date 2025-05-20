<?php

namespace Azuracom\SpreadsheetToObjectBundle\ColumnType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\MoneyTransformer;
use Symfony\Component\Form\DataTransformerInterface;

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