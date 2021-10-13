<?php

namespace Azuracom\SpreadsheetToObject\Factory;

use Azuracom\SpreadsheetToObject\Registry\ColumnTypeRegistry;
use Azuracom\SpreadsheetToObject\Spreadsheet\Handler;
use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
}
