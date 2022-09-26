<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObject\Exception\TransformationFailedException;

class ChoiceTransformer implements DataTransformerInterface
{

    private $choices;
    private $notFoundMessage;
    private $notFoundMessageShowValues;
    private $caseSensitive;

    public function __construct(
        array $choices,
        string $notFoundMessage,
        bool $notFoundMessageShowValues,
        bool $caseSensitive
    ) {
        $this->choices = $choices;
        $this->notFoundMessage = $notFoundMessage;
        $this->notFoundMessageShowValues = $notFoundMessageShowValues;
        $this->caseSensitive = $caseSensitive;
    }

    public function transform($value)
    {
        if ($value === null) {
            return null;
        }

        return array_search($value, $this->choices);
    }

    public function reverseTransform($value)
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

        throw new TransformationFailedException($this->notFoundMessage, 0, null,[
            '%value%' => (string) $value,
            '%values%' => $this->notFoundMessageShowValues ? ': "' . implode('", "', array_keys($this->choices)) . '"' : '',
        ]);
    }
}
