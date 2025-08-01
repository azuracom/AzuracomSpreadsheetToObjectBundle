<?php

namespace Azuracom\SpreadsheetToObjectBundle\Spreadsheet;

use Azuracom\SpreadsheetToObjectBundle\CellType\CellType;
use Azuracom\SpreadsheetToObjectBundle\CellType\CellTypeInterface;
use Azuracom\SpreadsheetToObjectBundle\Error\Error;
use Azuracom\SpreadsheetToObjectBundle\Event\Events;
use Azuracom\SpreadsheetToObjectBundle\Event\PostSetValueEvent;
use Azuracom\SpreadsheetToObjectBundle\Event\PostSetValuesEvent;
use Azuracom\SpreadsheetToObjectBundle\Event\PreSetValueEvent;
use Azuracom\SpreadsheetToObjectBundle\Event\PreSetValuesEvent;
use Azuracom\SpreadsheetToObjectBundle\Registry\CellTypeRegistry;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class Handler implements \Iterator, HandlerInterface
{
    const NOT_EDITABLE_CODE = 13;

    /** @var  CellTypeInterface[] */
    protected array $cellTypes = [];


    protected int $lastColumnIndex = 0;

    protected int $position = 0;

    protected int $currentRow = 0;

    protected string $currentKey = 'default';

    protected bool$trackChanges = false;

    protected bool $autoReset = true;

    /** @var Error[] */
    protected array $errors = [];

    protected array $changes = [];

    public function __construct(
        protected CellTypeRegistry $registry,
        protected ValidatorInterface $validator,
        protected EventDispatcherInterface $dispatcher,
        protected TranslatorInterface $translator
    ) {
    }

    public function add(string $name, ?string $type = null, array $options = []): static
    {
        $type = $type == null ? CellType::class : $type;

        /** @var CellTypeInterface */
        $child = clone $this->registry->getType($type);
        $child->setOwner($this);
        $child->init($name, $options);

        $this->cellTypes[] = $child;

        return $this;
    }

    public function remove(string $name, ?string $key = null): static
    {
        $key = $key ?? $this->getCurrentKey();
        foreach ($this->cellTypes as $index => $child) {
            if ($child->getName() === $name && $child->getOption('key') === $key) {
                unset($this->cellTypes[$index]);
                break;
            }
        }

        return $this;
    }   

    public function getDefaultColumn(): string
    {
        $column = self::int2ExcelColumn($this->lastColumnIndex);
        $this->lastColumnIndex++;

        return $column;
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);

        return $this;
    }

    public function setValues(Row|Worksheet $worksheetOrRow, string|array|null $keys = null): static
    {
        if (!$worksheetOrRow instanceof Row && !$worksheetOrRow instanceof Worksheet) {
            throw new \Exception(sprintf("Param worksheetOrRow should be instance of %s or %s", Row::class, Worksheet::class));
        }

        if ($worksheetOrRow instanceof Row) {
            $this->currentRow = $worksheetOrRow->getRowIndex();
            /** @var Worksheet */
            $worksheet = $worksheetOrRow->getWorksheet();
        } else {
            /** @var Worksheet */
            $worksheet = $worksheetOrRow;
        }

        $keys = is_string($keys) ? [$keys] : $keys;

        foreach ($this->cellTypes as $type) {
            if (is_array($keys) && !in_array($type->getOption('key'), $keys)) {
                continue;
            }

            $coordinate = $type->getColumn() . $this->getTypeRow($type);
            $cell = $worksheet->getCell($coordinate);
            $type->resetValues();
            $type->setValue($cell->getCalculatedValue());
        }

        return $this;
    }

    public function getTypeRow(CellTypeInterface $type): int
    {
        return $type->getOption('row') ? $type->getOption('row') : $this->currentRow;
    }


    public function setSheetHeader(Worksheet $sheet, int $rowNumber = 1): static
    {
        foreach ($this->cellTypes as $type) {
            $sheet->setCellValue($type->getColumn() . $rowNumber,  $this->translator->trans($type->getLabel()));
        }

        return $this;
    }

    public function setSheetHeaderComments(Worksheet $sheet, int $rowNumber = 1): static
    {
        foreach ($this->cellTypes as $type) {
            if ($comment = $type->getOption('column_comment')) {
                $sheet->getComment($type->getColumn() . $rowNumber)->getText()->createText($comment);
            }

            if (($width = $type->getOption('column_comment_width')) !== null) {
                $sheet->getComment($type->getColumn() . $rowNumber)->setWidth($width . 'pt');
            }
        }

        return $this;
    }

    public function setSheetColumnWidth(Worksheet $sheet): static
    {
        foreach ($this->cellTypes as $type) {
            if ($width = $type->getOption('column_width')) {
                $sheet->getColumnDimension($type->getColumn())->setWidth($width, 'pt');
            }
        }

        return $this;
    }

    public function setSheetRowContent(Worksheet $sheet, mixed $data, ?int $rowNumber = null, ?string $key = null): static
    {
        if ($rowNumber) {
            $this->currentRow = $rowNumber;
        }

        $key = $key ?? $this->getCurrentKey();

        foreach ($this->cellTypes as $type) {
            if (!$type->isDataMapped($data, $key)) {
                continue;
            }

            $coordinates = $type->getColumn() . $this->getTypeRow($type);
            $type->resetValues();
            $value = $type->getDataValue($data);
            $cell = $sheet->getCell($coordinates);
            $cell->setValue($value);

            if ($styles = $type->getOption('cell_styles')) {
                $styles = is_callable($styles) ? $styles($data, $value, $type) : $styles;
                $cell->getStyle()->applyFromArray($styles);
            }

            if ($cb = $type->getOption('cell_callback')) {
                $cb($cell, $data, $type);
            }
        }

        return $this;
    }

    public static function int2ExcelColumn(int $num): string
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return self::int2ExcelColumn($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }


    public function get(string $name, ?string $key = null): ?CellTypeInterface
    {
        $key = $key ?? $this->getCurrentKey();
        foreach ($this->cellTypes as $child) {
            if ($name === $child->getName() && $child->getOption('key') === $key) {
                return $child;
            }
        }

        return null;
    }

    public function setDataValues(mixed &$data, ?array $validationGroups = null, ?string $key = null): static
    {
        $key = $key ?? $this->getCurrentKey();
        if ($this->autoReset) {
            $this->resetChanges();
            $this->resetErrors();
        }

        if ($this->dispatcher->hasListeners(Events::PRE_SET_VALUES)) {
            $event = new PreSetValuesEvent($this, $data);
            $this->dispatcher->dispatch($event, Events::PRE_SET_VALUES);
            $data = $event->getData();
        }

        //first assign all values
        foreach ($this->cellTypes as $type) {
            //check if the conf is ok for the current data object and setter is required
            if (!$type->isDataMapped($data, $key) || ($type->getOption('setter') === false)) {
                continue;
            }

            $column = $type->getColumn();
            $columnLabel = $type->getOption('label');
            $row = $this->getTypeRow($type);

            //try to catch transformer exception            
            try {

                $newValue = $type->getValue();
                if ($this->dispatcher->hasListeners(Events::PRE_SET_VALUE)) {
                    $event = new PreSetValueEvent($this, $data, $newValue);
                    $this->dispatcher->dispatch($event, Events::PRE_SET_VALUE);
                    $newValue = $event->getValue();
                }

                $oldValue = $type->getDataValue($data, false);

                if ($type->hasChanged($newValue, $oldValue)) {
                    //validate conf contraints
                    $valueErrors = $this->validator->validate($newValue, $type->getOption('constraints'));
                    foreach ($valueErrors as $error) {
                        $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                            '%row%' => $row,
                            '%column%' => $column,
                            '%column_label%' => $columnLabel,
                            '%error%' => $error->getMessage()
                        ]);

                        $this->errors[] = new Error($message, $error->getCode(), $row, $column);
                    }

                    if ($type->dataCanBeUpdated($data, $newValue, $oldValue)) {
                        $type->setDataValue($data, $newValue);

                        //reset old value
                        if (count($valueErrors)) {
                            $type->setDataValue($data, $oldValue);
                        } elseif ($this->trackChanges) {
                            $oldStringValue = $type->getDataValue($data, true);
                            $newStringValue = $type->getValue(null);
                            $this->changes[$type->getLabel()] = "'$oldStringValue' => '$newStringValue'";
                        }
                    } elseif ($type->getOption('allow_update_error')) {
                        $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.value_not_editable", [
                            '%row%' => $row,
                            '%column%' => $column,
                            '%column_label%' => $columnLabel,
                        ]);

                        $this->errors[] = new Error($message, self::NOT_EDITABLE_CODE, $row, $column);
                    }
                }
            } catch (\Exception $e) {
                //transformation exception try to translate error
                $error = $e instanceof TransformationFailedException ?
                    $this->translator->trans($e->getMessage(), $e->getInvalidMessageParameters()) :
                    $e->getMessage();

                $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                    '%row%' => $row,
                    '%column%' => $column,
                    '%column_label%' => $columnLabel,
                    '%error%' => $error
                ]);

                $this->errors[] = new Error($message, $type->getOption('transformation_error_code'), $row, $column);
            }

            if ($this->dispatcher->hasListeners(Events::POST_SET_VALUE)) {
                $event = new PostSetValueEvent($this, $data, $newValue);
                $this->dispatcher->dispatch($event, Events::POST_SET_VALUE);
            }
        }

        if ($this->dispatcher->hasListeners(Events::POST_SET_VALUES)) {
            $event = new PostSetValuesEvent($this, $data);
            $this->dispatcher->dispatch($event, Events::POST_SET_VALUES);
            $data = $event->getData();
        }

        //revalidate full object
        $dataErrors = $this->validator->validate($data, null, $validationGroups);
        foreach ($dataErrors as $error) {
            //try to retrieve column using the propertyPath
            $name =  $error->getPropertyPath();
            $message = null;
            $column = null;
            $row = $this->currentRow;

            foreach ($this->cellTypes as $type) {
                $errorMatchPath = $type->getOption('error_match_path');
                if (
                    ($type->getName() == $name || ($errorMatchPath && preg_match("#$errorMatchPath#", $name))) &&
                    $type->isDataMapped($data, $key)
                ) {
                    $row = $this->getTypeRow($type);
                    $column = $type->getColumn();
                    $columnLabel = $type->getOption('label');

                    $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                        '%row%' => $row,
                        '%column%' => $column,
                        '%column_label%' => $columnLabel,
                        '%error%' =>  $error->getMessage()
                    ]);
                    break;
                }
            }
            if (!$message) {
                $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_property", [
                    '%row%' => $row,
                    '%property%' => $name,
                    '%error%' =>  $error->getMessage()
                ]);
            }

            $this->errors[] = new Error($message, $error->getCode(), $row, $column);
        }

        return $this;
    }

    public function getCellTypes(): array
    {
        return $this->cellTypes;
    }

    //iterator stuff
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->cellTypes[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->cellTypes[$this->position]);
    }

    /**
     * Get the value of currentKey
     */
    public function getCurrentKey(): string
    {
        return $this->currentKey;
    }

    /**
     * Set the value of currentKey
     *
     * @return  self
     */
    public function setCurrentKey(string $currentKey): static
    {
        $this->currentKey = $currentKey;

        return $this;
    }

    /**
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the value of changes
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    public function hasError(): bool
    {
        return count($this->errors) > 0;
    }

    public function hasChanged(): bool
    {
        return count($this->changes) > 0;
    }

    public function hasKey(string|int $key): bool
    {
        foreach ($this->cellTypes as $type) {
            if ($type->getOption('key') === $key) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get the value of trackChanges
     */
    public function getTrackChanges(): bool
    {
        return $this->trackChanges;
    }

    /**
     * Set the value of trackChanges
     *
     * @return  self
     */
    public function setTrackChanges(bool $trackChanges): static
    {
        $this->trackChanges = $trackChanges;

        return $this;
    }


    public function resetChanges(): static
    {
        $this->changes = [];

        return $this;
    }

    public function resetErrors(): static
    {
        $this->errors = [];

        return $this;
    }

    /**
     * Get the value of autoReset
     */
    public function getAutoReset(): bool
    {
        return $this->autoReset;
    }

    /**
     * Set the value of autoReset
     *
     * @return  self
     */
    public function setAutoReset(bool $autoReset): static
    {
        $this->autoReset = $autoReset;

        return $this;
    }

    public function getLastColumn(): ?string
    {
        $column = null;
        $maxValue = 0;
        foreach ($this->cellTypes as $CellType) {
            $value = Coordinate::columnIndexFromString($CellType->getColumn());
            if ($value > $maxValue) {
                $maxValue = $value;
                $column = $CellType->getColumn();
            }
        }

        return $column;
    }

    public function getSortedColumns(): array
    {
        $columns = [];
        foreach ($this->cellTypes as $CellType) {
            $index = Coordinate::columnIndexFromString($CellType->getColumn());
            $columns[$index] = $CellType;
        }

        ksort($columns);

        return $columns;
    }
}
