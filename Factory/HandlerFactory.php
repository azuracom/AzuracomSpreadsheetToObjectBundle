<?php

namespace Azuracom\SpreadsheetToObject\Factory;

use Azuracom\SpreadsheetToObject\Form\Type\ExportColumnCheckboxType;
use Azuracom\SpreadsheetToObject\Registry\ColumnTypeRegistry;
use Azuracom\SpreadsheetToObject\Spreadsheet\Handler;
use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

class HandlerFactory implements HandlerFactoryInterface
{
    /** @var  ColumnTypeInterface[] */
    protected $columnTypes = [];

    /** @var ColumnTypeRegistry */
    protected $registry;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var  EventDispatcherInterface */
    protected $dispatcher;

    protected $columnIndex;

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

    public function create(): HandlerInterface
    {
        $handler = new Handler(
            $this->registry,
            $this->validator,
            $this->dispatcher,
            $this->translator
        );

        return $handler;
    }

    public function createFromForm(FormInterface $form, bool $selectAllWhenEmpty = true): HandlerInterface
    {
        $handler = $this->create();
        $forceSelected =  $this->isEmpty($form) && $selectAllWhenEmpty;
        $this->columnIndex = 1;
        $this->iterateOnFormR($form, $handler, $forceSelected);
        return $handler;
    }

    private function isEmpty(FormInterface $form): bool
    {
        foreach ($form->all() as $field) {
            if ($field->count()) {
                $res = $this->isEmpty($field);
                if($res === false) {
                    return false;
                }
            }

            if ($field->getConfig()->getType()->getInnerType() instanceof ExportColumnCheckboxType && $field->get('selected')->getData()) {
                return false;
            }
        }

        return true;
    }

    private function iterateOnFormR(FormInterface $form, HandlerInterface $handler, bool $forceSelected)
    {
        foreach ($form->all() as $field) {
            if ($field->count()) {
                $this->iterateOnFormR($field, $handler, $forceSelected);
            }

            if (
                $field->getConfig()->getType()->getInnerType() instanceof ExportColumnCheckboxType && 
                ($field->get('selected')->getData() || $forceSelected)
            ) {

                if($forceSelected) {
                    $column = Coordinate::stringFromColumnIndex($this->columnIndex);
                    $this->columnIndex++;
                }else{
                    $column = $field->get('column')->getData();
                }

                $fieldOptions = $field->getConfig()->getOptions();
                $options = $fieldOptions['column_options'];
                $options['label'] = $fieldOptions['label'];
                $options['column'] = $column;
                $options['key'] = isset($options['key']) ? $options['key'] : $fieldOptions['column_key'];
                $name = $fieldOptions['column_name'] ? $fieldOptions['column_name'] : $field->getName();
                $type = $fieldOptions['column_type'];

                $handler->add($name, $type, $options);
            }
        }
    }
}
