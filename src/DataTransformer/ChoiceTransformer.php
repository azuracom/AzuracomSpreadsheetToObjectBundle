<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ChoiceTransformer implements DataTransformerInterface
{

    public function __construct(
        private array $choices,
        private string $notFoundMessage,
        private bool $notFoundMessageShowValues,
        private bool $caseSensitive
    ) {
    }

    public function transform(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return array_search($value, $this->choices);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = $this->caseSensitive ? $value : strtolower($value);

        foreach ($this->choices as $key => $choice) {
            $key = $this->caseSensitive ? $key : strtolower($key);
            if ($key == $value) {
                return $choice;
            }
        }

        throw new TransformationFailedException($this->notFoundMessage, 0, null, null, [
            '%value%' => (string) $value,
            '%values%' => $this->notFoundMessageShowValues ? ': "' . implode('", "', array_keys($this->choices)) . '"' : '',
        ]);
    }
}
