<?php

namespace Azuracom\SpreadsheetToObjectBundle\ColumnType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\PercentTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class PercentType extends AbstractType
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