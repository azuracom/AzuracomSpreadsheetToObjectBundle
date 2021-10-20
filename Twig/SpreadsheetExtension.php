<?php

namespace Azuracom\SpreadsheetToObject\Twig;

use Azuracom\SpreadsheetToObject\Spreadsheet\Handler;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class SpreadsheetExtension extends AbstractExtension
{

    public function getFunctions()
    {
        return [
            new TwigFunction('getColumnFromForm', [$this, 'getColumnFromForm']),
        ];
    }

    public function getColumnFromForm(FormView $formView)
    {
        $index = 0;
        $columns = [];
        $this->loopRecursiveOnForm($formView, $index, $columns);
        return $columns;
    }

    private function loopRecursiveOnForm(FormView $formView, &$index, &$columns)
    {
        foreach ($formView as $child) {
            if ($child->vars['block_prefixes'][1] === 'export_column_checkbox') {
                $columns[] = Handler::int2ExcelColumn($index);
                $index++;
            }
            $this->loopRecursiveOnForm($child, $index, $columns);
        }
    }
}
