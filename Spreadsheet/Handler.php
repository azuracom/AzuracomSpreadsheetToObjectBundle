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
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Azuracom\SpreadsheetToObject\Exception\TransformationFailedException;
use Symfony\Component\Translation\TranslatorInterface;

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
    protected $columnWidthSetted = false;

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

            $type->setValue($cell->getValue());
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

    public function setSheetRowContent(Worksheet $sheet, $data, ?int $rowNumber = null, ?string $key = null): HandlerInterface
    {
        if ($rowNumber) {
            $this->currentRow = $rowNumber;
        }

        $key = $key ?? $this->getCurrentKey();

        foreach ($this->columnTypes as $type) {
            if (!$this->columnWidthSetted && $type->getOption('column_width')) {
                $sheet->getColumnDimension($type->getColumn())->setWidth($type->getOption('column_width'), 'pt');
            }

            if ($type->isDataMapped($data, $key)) {
                $coordinates = $type->getColumn() . $this->getTypeRow($type);
                $value = $type->getDataValue($data);
                $sheet->setCellValue($coordinates, $value);

                if ($styles = $type->getOption('cell_styles')) {
                    $styles = is_callable($styles) ? $styles($data, $value, $type) : $styles;
                    $sheet->getCell($coordinates)->getStyle()->applyFromArray($styles);
                }
            }
        }

        $this->columnWidthSetted = true;

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
            $this->dispatcher->dispatch(Events::PRE_SET_VALUES, $event);
            $data = $event->getData();
        }

        //first assign all values
        foreach ($this->columnTypes as $type) {
            //check if the conf is ok for the current data object and setter is required
            if (!$type->isDataMapped($data, $key) || ($type->getOption('setter') === false)) {
                continue;
            }

            $column = $type->getColumn();
            $row = $this->getTypeRow($type);

            //try to catch transformer exception            
            try {

                $newValue = $type->getValue();
                if ($this->dispatcher->hasListeners(Events::PRE_SET_VALUE)) {
                    $event = new PreSetValueEvent($this, $data, $newValue);
                    $this->dispatcher->dispatch(Events::PRE_SET_VALUE, $event);
                    $newValue = $event->getValue();
                }

                $oldValue = $type->getDataValue($data, false);

                if ($type->hasChanged($newValue, $oldValue)) {
                    if ($type->dataCanBeUpdated($data)) {

                        $type->setDataValue($data, $newValue);

                        //validate conf contraints
                        $valueErrors = $this->validator->validate($newValue, $type->getOption('constraints'));
                        foreach ($valueErrors as $error) {
                            $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                                '%row%' => $row,
                                '%column%' => $column,
                                '%error%' => $error->getMessage()
                            ]);

                            $this->errors[] = new Error($message, $error->getCode(),$row,$column);
                        }

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
                            '%row%' => $this->getTypeRow($type),
                            '%column%' => $type->getColumn(),
                        ]);

                        $this->errors[] = new Error($message, self::NOT_EDITABLE_CODE,$row,$column);
                    }
                }
            } catch (\Exception $e) {
                //transformation exception try to translate error
                $error = $e instanceof TransformationFailedException ?
                    $this->translator->trans($e->getMessage(), $e->getInvalidMessageParameters()) :
                    $e->getMessage();

                $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                    '%row%' => $this->getTypeRow($type),
                    '%column%' => $type->getColumn(),
                    '%error%' => $error
                ]);

                $this->errors[] = new Error($message, $type->getOption('transformation_error_code'),$row,$column);
            }

            if ($this->dispatcher->hasListeners(Events::POST_SET_VALUE)) {
                $event = new PostSetValueEvent($this, $data, $newValue);
                $this->dispatcher->dispatch(Events::POST_SET_VALUE, $event);
            }
        }

        if ($this->dispatcher->hasListeners(Events::POST_SET_VALUES)) {
            $event = new PostSetValuesEvent($this, $data);
            $this->dispatcher->dispatch(Events::POST_SET_VALUES, $event);
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
                    $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                        '%row%' => $row,
                        '%column%' => $column,
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

            $this->errors[] = new Error($message, $error->getCode(),$row,$column);
        }

        return $this;
    }

    public function getColumnTypes(): array
    {
        return $this->columnTypes;
    }

    //iterator stuff
    public function rewind()
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

    public function next()
    {
        ++$this->position;
    }

    public function valid()
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
     * Get the value of columnWidthSetted
     */
    public function getColumnWidthSetted(): bool
    {
        return $this->columnWidthSetted;
    }

    /**
     * Set the value of columnWidthSetted
     *
     * @return  self
     */
    public function setColumnWidthSetted(bool $columnWidthSetted): HandlerInterface
    {
        $this->columnWidthSetted = $columnWidthSetted;

        return $this;
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
}
