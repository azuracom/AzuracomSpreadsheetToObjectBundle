<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;

class DateTimeTransformer implements DataTransformerInterface
{


    public function __construct(
        private string $format,
        private bool $time = true
    ) {}

    public function transform(mixed $datetime): mixed
    {
        return $datetime ? $datetime->format($this->format) : null;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = \DateTime::createFromFormat($this->format, $value);
        if ($value === false) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.datetime");
        }

        if (!$this->time) {
            $value->setTime(0, 0, 0);
        }

        return $value;
    }
}
