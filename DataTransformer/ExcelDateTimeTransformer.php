<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ExcelDateTimeTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if ($value === null) {
            return $value;
        }

        return Date::dateTimeToExcel($value);
    }

    public function reverseTransform($value)
    {
        if ($value === null) {
            return $value;
        }


        if (!is_numeric($value)) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.excel_datetime");
        }

        return Date::excelToDateTimeObject($value);
    }
}
