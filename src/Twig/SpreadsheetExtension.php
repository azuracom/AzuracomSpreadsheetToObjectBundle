<?php

namespace Azuracom\SpreadsheetToObjectBundle\Twig;

use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\Handler;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SpreadsheetExtension extends AbstractExtension
{

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getColumnFromForm', [$this, 'getColumnFromForm']),
        ];
    }

    public function getColumnFromForm(FormView $formView): array
    {
        $index = 0;
        $columns = [];
        $this->loopRecursiveOnForm($formView, $index, $columns);
        return $columns;
    }

    private function loopRecursiveOnForm(FormView $formView, int &$index, array &$columns): void
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
