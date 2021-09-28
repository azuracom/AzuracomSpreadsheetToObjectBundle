<?php

namespace Azuracom\SpreadsheetToObject\Spreadsheet;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnType;
use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
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
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class RowHandler implements \Iterator, RowHandlerInterface
{
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

    public function add(string $name, ?string $type = null, array $options = [])
    {
        $column = self::int2ExcelColumn($this->lastColumnIndex);
        $type = $type == null ? ColumnType::class : $type;
        $options['key'] = isset($options['key']) ? $options['key'] : $this->currentKey;

        $child = clone $this->registry->getType($type);
        $child->init($column, $name, $options);
        $child->setOwner($this);

        $this->columnTypes[] = $child;
        $this->lastColumnIndex++;

        return $this;
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0)
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);

        return $this;
    }

    public function setRowValues(Row $row)
    {
        $this->currentRow = $row->getRowIndex();

        foreach ($row->getCellIterator('A', $this->getLastColumn()) as $column => $cell) {
            $type = $this->getByColumn($column);
            $type->setValue($cell->getValue());
        }
    }

    public function setSheetHeader(Worksheet $sheet, int $rowNumber = 1)
    {
        foreach ($this->columnTypes as $type) {
            $sheet->setCellValue($type->getColumn() . $rowNumber,  $type->getLabel());
        }
    }

    public function setSheetRowContent(Worksheet $sheet, int $rowNumber, $data, string $key = 'default')
    {
        foreach ($this->columnTypes as $type) {
            if ($type->isDataMapped($data, $key)) {
                $sheet->setCellValue($type->getColumn() . $rowNumber, $type->getDataValue($data));
            }
        }
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


    public function get(string $name, string $key = 'default'): ?ColumnTypeInterface
    {
        foreach ($this->columnTypes as $child) {
            if ($name === $child->getName() && $child->getOption('key') === $key) {
                return $child;
            }
        }

        return null;
    }

    public function getByColumn($column)
    {
        foreach ($this->columnTypes as $child) {
            if ($column === $child->getColumn()) {
                return $child;
            }
        }

        return null;
    }

    public function setDataValues($data, ?array $validationGroups = null, $key = 'default')
    {
        $errors = [];
        $changes = [];

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
                    if ($type->dataCanBeUpdated($data)) {
                        $oldStringValue = $type->getDataValue($data, true);
                        $newStringValue = $type->getValue(null);

                        $changes[$type->getLabel()] = "'$oldStringValue' => '$newStringValue'";
                        $type->setDataValue($data, $newValue);

                        //validate conf contraints
                        $valueErrors = $this->validator->validate($newValue, $type->getOption('constraints'));
                        foreach ($valueErrors as $error) {
                            $errors[] = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                                '%row%' => $this->currentRow,
                                '%column%' => $type->getColumn(),
                                '%error%' => $error->getMessage()
                            ]);
                        }
                    } else {
                        $errors[] = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.value_not_editable", [
                            '%row%' => $this->currentRow,
                            '%column%' => $type->getColumn(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                //transformation exception try to translate error
                $error = $e instanceof TransformationFailedException ?
                    $this->translator->trans($e->getMessage(), $e->getInvalidMessageParameters()) :
                    $e->getMessage();

                $errors[] = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                    '%row%' => $this->currentRow,
                    '%column%' => $type->getColumn(),
                    '%error%' => $error
                ]);
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
            foreach ($this->columnTypes as $type) {
                $errorMatchPath = $type->getOption('error_match_path');
                if ($type->getName() == $name || ($errorMatchPath && preg_match("#$errorMatchPath#", $name))) {
                    $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_column", [
                        '%row%' => $this->currentRow,
                        '%column%' => $type->getColumn(),
                        '%error%' =>  $error->getMessage()
                    ]);
                    break;
                }
            }
            if (!$message) {
                $message = $this->translator->trans("azuracom_spreadsheet_to_object.row_handler.error_at_property", [
                    '%row%' => $this->currentRow,
                    '%column%' => $name,
                    '%error%' =>  $error->getMessage()
                ]);
            }
            $errors[] = $message;
        }

        return [
            'errors' => $errors,
            'changes' => $changes
        ];
    }

    public function getColumnTypes(): array
    {
        return $this->columnTypes;
    }

    public function getLastColumn()
    {
        $key = array_key_last($this->columnTypes);
        return $this->columnTypes[$key]->getColumn();
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
    public function getCurrentKey()
    {
        return $this->currentKey;
    }

    /**
     * Set the value of currentKey
     *
     * @return  self
     */
    public function setCurrentKey($currentKey)
    {
        $this->currentKey = $currentKey;

        return $this;
    }
}
