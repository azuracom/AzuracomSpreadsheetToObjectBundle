<?php

namespace Azuracom\SpreadsheetToObjectBundle\Spreadsheet;

use Azuracom\SpreadsheetToObjectBundle\CellType\CellTypeInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface HandlerInterface
{
    public function add(string $name, ?string $type = null, array $options = []): static;
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static;
    public function setValues(Row|Worksheet $worksheetOrRow,string|array|null $keys = null): static;
    public function setSheetHeader(Worksheet $sheet, int $rowNumber = 1): static;
    public function setSheetHeaderComments(Worksheet $sheet, int $rowNumber = 1): static;
    public function setSheetRowContent(Worksheet $sheet,mixed $data, ?int $rowNumber = null, ?string $key = null): static;
    public function get(string $name, ?string $key = null): ?CellTypeInterface;
    public function setDataValues(mixed &$data, ?array $validationGroups = null, ?string $key = null): static;
    public function getCurrentKey(): string;
    public function setCurrentKey(string $currentKey): static;
    public function getDefaultColumn(): string;
    public function getErrors(): array;
    public function getChanges(): array;
    public function hasError(): bool;
    public function hasChanged(): bool;
    public function hasKey(string|int $key): bool;
    public function setSheetColumnWidth(Worksheet $sheet): static;
    public function getTrackChanges(): bool;
    public function setTrackChanges(bool $trackChanges): static;
    public function resetChanges(): static;
    public function resetErrors(): static;
    public function getAutoReset(): bool;
    public function setAutoReset(bool $autoReset): static;
    public function getLastColumn(): ?string;
}
