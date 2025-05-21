<?php

namespace Azuracom\SpreadsheetToObjectBundle\Factory;

use Azuracom\SpreadsheetToObjectBundle\Form\Type\ExportColumnCheckboxType;
use Azuracom\SpreadsheetToObjectBundle\Registry\CellTypeRegistry;
use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\Handler;
use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\HandlerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

class HandlerFactory implements HandlerFactoryInterface
{
    /** @var  CellTypeInterface[] */
    protected array $CellTypes = [];

    protected ?int $columnIndex = null;

    public function __construct(
        protected CellTypeRegistry $registry,
        protected ValidatorInterface $validator,
        protected EventDispatcherInterface $dispatcher,
        protected TranslatorInterface $translator
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

    private function iterateOnFormR(FormInterface $form, HandlerInterface $handler, bool $forceSelected): void
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
                $options = $fieldOptions['cell_options'];
                $options['label'] = $fieldOptions['label'];
                $options['column'] = $column;
                $options['key'] = isset($options['key']) ? $options['key'] : $fieldOptions['cell_key'];
                $name = $fieldOptions['cell_name'] ? $fieldOptions['cell_name'] : $field->getName();
                $type = $fieldOptions['cell_type'];

                $handler->add($name, $type, $options);
            }
        }
    }
}
