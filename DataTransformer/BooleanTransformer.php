<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObject\Exception\TransformationFailedException;

class BooleanTransformer implements DataTransformerInterface
{
    /** @var array */
    private $trueValues;

    /** @var array */
    private $falseValues;

    public function __construct(array $trueValues = [1, '1', 'yes', 'y'], array $falseValues = [0, '0', 'no', 'n'])
    {
        $this->trueValues = $trueValues;
        $this->falseValues = $falseValues;
    }
    public function transform($boolvalue)
    {
        return $boolvalue ? 1 : 0;
    }

    public function reverseTransform($stringValue)
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

        throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.boolean", 0, null, [
            '%true_values%' => '"'.implode('", "', $this->trueValues).'"',
            '%false_values%' => '"'.implode('", "', $this->falseValues).'"',
        ]);
    }
}
