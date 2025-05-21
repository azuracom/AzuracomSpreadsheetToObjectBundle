<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class BooleanTransformer implements DataTransformerInterface
{

    public function __construct(
        private array $trueValues,
        private array $falseValues
    ) {
        if (count($trueValues) === 0 || count($falseValues) === 0) {
            throw new \LogicException("True values and false values should countains at least one element");
        }
    }
    public function transform(mixed $boolvalue): mixed
    {
        if ($boolvalue === null) {
            return null;
        }

        return $boolvalue ? $this->trueValues[0] : $this->falseValues[0];
    }

    public function reverseTransform(mixed $stringValue): mixed
    {
        if ($stringValue === null) {
            return null;
        }

        foreach ($this->trueValues as $value) {
            if ($stringValue === $value) {
                return true;
            }
        }

        foreach ($this->falseValues as $value) {
            if ($stringValue === $value) {
                return false;
            }
        }

        throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.boolean", 0, null, null, [
            '%true_values%' => '"' . implode('", "', $this->trueValues) . '"',
            '%false_values%' => '"' . implode('", "', $this->falseValues) . '"',
        ]);
    }
}
