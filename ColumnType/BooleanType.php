<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\BooleanTransformer;
use Symfony\Component\Form\DataTransformerInterface;

class BooleanType extends AbstractType
{
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new BooleanTransformer();
    }

    public function hasChangedInner($newValue,$oldValue) :bool
    {
        return (int) $newValue !== (int) $oldValue;
    }
}