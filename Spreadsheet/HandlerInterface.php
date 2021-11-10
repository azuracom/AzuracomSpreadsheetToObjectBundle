<?php

namespace Azuracom\SpreadsheetToObject\Spreadsheet;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface HandlerInterface
{
    public function add(string $name, ?string $type = null, array $options = []): self;
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): self;
    public function setValues($worksheetOrRow, $keys = null): self;
    public function setSheetHeader(Worksheet $sheet, int $rowNumber = 1): self;
    public function setSheetRowContent(Worksheet $sheet, $data, ?int $rowNumber = null, ?string $key = null): self;
    public function get(string $name, ?string $key = null): ?ColumnTypeInterface;
    public function setDataValues($data, ?array $validationGroups = null, ?string $key = null): HandlerInterface;
    public function getCurrentKey(): string;
    public function setCurrentKey(string $currentKey): self;
    public function getDefaultColumn(): string;
    public function getErrors(): array;
    public function getChanges(): array;
    public function hasError(): bool;
    public function hasChanged(): bool;
    public function hasKey($key): bool;
    public function getColumnWidthSetted(): bool;
    public function setColumnWidthSetted(bool $columnWidthSetted): HandlerInterface;
    public function getTrackChanges() : bool;
    public function setTrackChanges(bool $trackChanges) : HandlerInterface;
}
