<?php

namespace Azuracom\SpreadsheetToObject\Factory;

use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;

interface HandlerFactoryInterface
{
    public function create() : HandlerInterface;
}