<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

interface DataTransformerInterface
{
    public function transform($value);
    public function reverseTransform($value);
}