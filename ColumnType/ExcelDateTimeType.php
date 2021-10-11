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
}