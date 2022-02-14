<?php

namespace Azuracom\SpreadsheetToObject\Spreadsheet;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnType;
use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use Azuracom\SpreadsheetToObject\Error\Error;
use Azuracom\SpreadsheetToObject\Event\Events;
use Azuracom\SpreadsheetToObject\Event\PostSetValueEvent;
use Azuracom\SpreadsheetToObject\Event\PostSetValuesEvent;
use Azuracom\SpreadsheetToObject\Event\PreSetValueEvent;
use Azuracom\SpreadsheetToObject\Event\PreSetValuesEvent;
use Azuracom\SpreadsheetToObject\Registry\ColumnTypeRegistry;
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

    /** @var  ColumnTypeInterface[] */
    protected $columnTypes = [];

    /** @var ColumnTypeRegistry */
    protected $registry;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var TranslatorInterface */
    protected $translator;

    protected $dispatcher;

    protected $lastColumnIndex = 0;

    protected $position = 0;

    protected $currentRow = 0;

    protected $currentKey = 'default';

    /** @var boolean */
    protected $trackChanges = false;

    /** @var boolean */
    protected $autoReset = true;

    /** @var Error[] */
    protected $errors = [];

    /** @var array */
    protected $changes = [];

    public function __construct(
        ColumnTypeRegistry $registry,
        ValidatorInterface $validator,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->validator = $validator;
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
    }

    public function add(string $name, ?string $type = null, array $options = []): HandlerInterface
    {
        $type = $type == null ? ColumnType::class : $type;

        /** @var ColumnTypeInterface */
        $child = clone $this->registry->getType($type);
        $child->setOwner($this);
        $child->init($name, $options);

        $this->columnTypes[] = $child;

        return $this;
    }

    public function getDefaultColumn(): string
    {
        $column = self::int2ExcelColumn($this->lastColumnIndex);
        $this->lastColumnIndex++;

        return $column;
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): HandlerInterface
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);

        return $this;
    }

    public function setValues($worksheetOrRow, $keys = null): HandlerInterface
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

        foreach ($this->columnTypes as $type) {
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

    public function getTypeRow(ColumnTypeInterface $type)
    {
        return $type->getOption('row') ? $type->getOption('row') : $this->currentRow;
    }


    public function setSheetHeader(Worksheet $sheet, int $rowNumber = 1): HandlerInterface
    {
        foreach ($this->columnTypes as $type) {
            $sheet->setCellValue($type->getColumn() . $rowNumber,  $this->translator->trans($type->getLabel()));
        }

        return $this;
    }

    public function setSheetHeaderComments(Worksheet $sheet, int $rowNumber = 1): HandlerInterface
    {
        foreach ($this->columnTypes as $type) {
            if ($comment = $type->getOption('column_comment')) {
                $sheet->getComment($type->getColumn() . $rowNumber)->getText()->createText($comment);
            }
        }

        return $this;
    }

    public function setSheetColumnWidth(Worksheet $sheet): HandlerInterface
    {
        foreach ($this->columnTypes as $type) {
            if ($width = $type->getOption('column_width')) {
                $sheet->getColumnDimension($type->getColumn())->setWidth($width, 'pt');
            }
        }

        return $this;
    }

    public function setSheetRowContent(Worksheet $sheet, $data, ?int $rowNumber = null, ?string $key = null): HandlerInterface
    {
        if ($rowNumber) {
            $this->currentRow = $rowNumber;
        }

        $key = $key ?? $this->getCurrentKey();

        foreach ($this->columnTypes as $type) {
            if ($type->isDataMapped($data, $key)) {
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
        }

        return $this;
    }

    public static function int2ExcelColumn($num)
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


    public function get(string $name, ?string $key = null): ?ColumnTypeInterface
    {
        $key = $key ?? $this->getCurrentKey();
        foreach ($this->columnTypes as $child) {
            if ($name === $child->getName() && $child->getOption('key') === $key) {
                return $child;
            }
        }

        return null;
    }

    public function setDataValues($data, ?array $validationGroups = null, ?string $key = null): HandlerInterface
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
        foreach ($this->columnTypes as $type) {
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

                if ($type->hasChanged($newValue, $oldValue)) {
                    if ($type->dataCanBeUpdated($data)) {

                        $type->setDataValue($data, $newValue);

                        //reset old value
                        if (count($valueErrors)) {
                            $type->setDataValue($data, $oldValue);
                        } elseif ($this->trackChanges) {
                            $oldStringValue = $type->getDataValue($data, true);
                            $newStringValue = $type->getValue(null);
                            $this->changes[$type->getLabel()] = "'$oldStringValue' => '$newStringValue'";
                        }
                    } else {
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

            foreach ($this->columnTypes as $type) {
                $errorMatchPath = $type->getOption('error_match_path');
                if ($type->getName() == $name || ($errorMatchPath && preg_match("#$errorMatchPath#", $name))) {
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

    public function getColumnTypes(): array
    {
        return $this->columnTypes;
    }

    //iterator stuff
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->columnTypes[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->columnTypes[$this->position]);
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
    public function setCurrentKey(string $currentKey): HandlerInterface
    {
        $this->currentKey = $currentKey;

        return $this;
    }

    /**
     * Get the value of errors
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

    public function hasKey($key): bool
    {
        foreach ($this->columnTypes as $type) {
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
    public function setTrackChanges($trackChanges): HandlerInterface
    {
        $this->trackChanges = $trackChanges;

        return $this;
    }


    public function resetChanges(): HandlerInterface
    {
        $this->changes = [];

        return $this;
    }

    public function resetErrors(): HandlerInterface
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
    public function setAutoReset(bool $autoReset): HandlerInterface
    {
        $this->autoReset = $autoReset;

        return $this;
    }

    public function getLastColumn(): ?string
    {
        $column = null;
        $maxValue = 0;
        foreach ($this->columnTypes as $columnType) {
            $value = Coordinate::columnIndexFromString($columnType->getColumn());
            if ($value > $maxValue) {
                $maxValue = $value;
                $column = $columnType->getColumn();
            }
        }

        return $column;
    }
}
