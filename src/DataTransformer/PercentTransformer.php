<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class PercentTransformer implements DataTransformerInterface
{
    private $decimalTransformer;
    
    public function __construct()
    {
        $this->decimalTransformer = new DecimalTransformer();
    }

    public function transform(mixed $decimalValue): mixed
    {
        $value = $this->decimalTransformer->transform($decimalValue);
        return $value === null ? null : $value * 100;
    }

    public function reverseTransform(mixed $stringValue): mixed
    {
        $value = $this->decimalTransformer->reverseTransform($stringValue);
        return $value === null ? null : $value / 100;
    }
}
