<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class PercentTransformer implements DataTransformerInterface
{
    private $decimalTransformer;
    
    public function __construct()
    {
        $this->decimalTransformer = new DecimalTransformer();
    }

    public function transform($decimalValue)
    {
        $value = $this->decimalTransformer->transform($decimalValue);
        return $value === null ? null : $value * 100;
    }

    public function reverseTransform($stringValue)
    {
        $value = $this->decimalTransformer->reverseTransform($stringValue);
        return $value === null ? null : $value / 100;
    }
}
