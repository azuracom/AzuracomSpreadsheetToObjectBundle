<?php

namespace Azuracom\SpreadsheetToObject\Factory;

use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use Symfony\Component\Form\FormInterface;

interface HandlerFactoryInterface
{
    public function create() : HandlerInterface;

    public function createFromForm(FormInterface $form) : HandlerInterface;
}