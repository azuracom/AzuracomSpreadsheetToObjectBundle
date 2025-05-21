<?php

namespace Azuracom\SpreadsheetToObjectBundle\Factory;

use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\HandlerInterface;
use Symfony\Component\Form\FormInterface;

interface HandlerFactoryInterface
{
    public function create(): HandlerInterface;

    public function createFromForm(FormInterface $form): HandlerInterface;
}
