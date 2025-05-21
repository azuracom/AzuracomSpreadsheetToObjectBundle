<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class StringTransformer implements DataTransformerInterface
{
    public function transform(mixed $v): mixed
    {
        if ($v === null) {
            return null;
        }

        return (string) $v;
    }

    public function reverseTransform(mixed $v): mixed
    {
        return $this->transform($v);
    }
}
