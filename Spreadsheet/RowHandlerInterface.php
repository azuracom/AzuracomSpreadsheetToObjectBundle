<?php

namespace Azuracom\SpreadsheetToObject\Spreadsheet;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface RowHandlerInterface
{
    public function add(string $name,?string $type = null,array $options = []);
    public function addEventListener(string $eventName,callable $listener,int $priority = 0);
    public function setRowValues(Row $row);
    public function setSheetHeader(Worksheet $sheet,int $rowNumber = 1);
    public function setSheetRowContent(Worksheet $sheet,int $rowNumber,$data,string $key = 'default');
    public function get(string $name,string $key = 'default'): ?ColumnTypeInterface;
    public function setDataValues($data,?array $validationGroups = null,$key = 'default');
    
}