<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\ExcelDateTimeTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class ExcelDateTimeType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new ExcelDateTimeTransformer();
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        if ($newValue instanceof \DateTime && $oldValue instanceof \DateTime) {
            return $newValue->getTimestamp() !== $oldValue->getTimestamp();
        }
        return true;
    }
}
