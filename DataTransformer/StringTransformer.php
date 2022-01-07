<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StringTransformer implements DataTransformerInterface
{
    public function transform($v)
    {
        if ($v === null) {
            return null;
        }

        return (string) $v;
    }

    public function reverseTransform($v)
    {
        return $this->transform($v);
    }
}
