<?php

namespace Azuracom\SpreadsheetToObject\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class RangeReadFilter implements IReadFilter
{

    private $columns  = array();

    public function __construct($columns)
    {
        $this->columns  = $columns;
    }

    public function readCell($column, $row, $worksheetName = '')
    {

        if (in_array($column, $this->columns)) {
            return true;
        }
        return false;
    }
}
