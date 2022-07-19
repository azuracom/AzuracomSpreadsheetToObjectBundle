<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EnumTransformer implements DataTransformerInterface
{
    private $class;
    private $labelCb;
    private $notFoundMessage;

    public function __construct(
        string $class,
        ?callable $labelCb,
        string $notFoundMessage
    ) {
        $this->class = $class;
        $this->labelCb = $labelCb;
        $this->notFoundMessage = $notFoundMessage;
    }

    public function transform($value)
    {
        if (!$value) {
            return null;
        }

        $cb = $this->labelCb;
        return $cb($value);
    }

    public function reverseTransform($value)
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
