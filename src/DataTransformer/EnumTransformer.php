<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EnumTransformer implements DataTransformerInterface
{
    public function __construct(
        private string $class,
        private mixed $labelCb,
        private string $notFoundMessage
    ) {
    }

    public function transform(mixed $value): mixed
    {
        if (!$value) {
            return null;
        }

        $cb = $this->labelCb;
        return $cb($value);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (!$value) {
            return null;
        }

        $cb = $this->labelCb;

        foreach ($this->class::cases() as $case) {
            $match =  $cb($case);
            if ($match === $value) {
                return $case;
            }
        }

        throw new TransformationFailedException($this->notFoundMessage, 0, null, null, [
            '%value%' => (string) $value,
            '%values%' => ' "' . implode('", "', array_map(function ($enum) use ($cb) {
                return $cb($enum);
            }, $this->class::cases())) . '"',
        ]);
    }
}
