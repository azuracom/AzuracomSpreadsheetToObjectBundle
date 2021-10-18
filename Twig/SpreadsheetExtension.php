<?php

namespace Azuracom\SpreadsheetToObject\Twig;

use Azuracom\SpreadsheetToObject\Spreadsheet\Handler;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SpreadsheetExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('int_to_excel_column', [$this, 'int2ExcelColumn']),
        ];
    }

    public function int2ExcelColumn($index)
    {
        return Handler::int2ExcelColumn($index);
    }
}
