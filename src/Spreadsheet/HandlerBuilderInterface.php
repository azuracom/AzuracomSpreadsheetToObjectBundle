<?php


namespace Azuracom\SpreadsheetToObjectBundle\Spreadsheet;

interface HandlerBuilderInterface
{
    public function build(?string $format = null): HandlerInterface;
}