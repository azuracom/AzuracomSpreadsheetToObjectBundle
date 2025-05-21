<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class AttributeValueSelectValueTransformer implements DataTransformerInterface
{

    public function __construct(
        private AttributeInterface $attribute,
        private string $defaultLocale,
        private string $choiceSeparator = ',',
        private bool $caseSensitive = false,
        private ?callable $createChoiceCallback = null
    ) {
        if ($attribute->getType() !== SelectAttributeType::TYPE) {
            throw new \LogicException(sprintf("Attribute should be of type %s", SelectAttributeType::TYPE));
        }
    }

    public function transform(mixed $value): mixed
    {
        if (!$value) {
            return null;
        }

        $choices = $this->attribute->getConfiguration()['choices'];
        $string = "";
        foreach ($value as $choice) {
            if (!isset($choices[$choice]) || !isset($choices[$choice][$this->defaultLocale])) {
                continue;
            }

            $string .= $choices[$choice][$this->defaultLocale] . $this->choiceSeparator;
        }

        return $string ? substr($string, 0, -1) : "";
    }


    public function reverseTransform(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $multiple = $this->attribute->getConfiguration()['multiple'];
        $choices = $this->attribute->getConfiguration()['choices'];

        $results = [];

        foreach (explode($this->choiceSeparator, $value) as $choice) {
            $foundedKey = null;
            $values = [];
            $choiceValue = $this->caseSensitive ? $choice : strtolower($choice);

            foreach ($choices as $key => $tmpChoice) {
                if (isset($tmpChoice[$this->defaultLocale])) {
                    $tmpChoiceValue = $tmpChoice[$this->defaultLocale];
                    $values[] = $tmpChoiceValue;
                    $tmpChoiceValue = $this->caseSensitive ? $tmpChoiceValue : strtolower($tmpChoiceValue);

                    if (trim($choiceValue) == $tmpChoiceValue) {
                        $foundedKey = $key;
                        break;
                    }
                }
            }

            if (!$foundedKey) {

                if ($this->createChoiceCallback) {
                    $foundedKey = call_user_func_array($this->createChoiceCallback, [$choice, $this->attribute, $this->defaultLocale]);
                    if (!$foundedKey) {
                        throw new \LogicException("azuracom_spreadsheet_to_object.data_transformer_exception.create_choice_callback_return_null");
                    }
                } else {
                    throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.choice_value_not_found", 0, null, null, [
                        '%value%' => (string) $choice,
                        '%values%' => ": " . implode(', ', $values),
                    ]);
                }
            }

            $results[] = $foundedKey;
        }

        if (!$multiple && count($results) > 1) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.attribute_value_multiple_not_allowed");
        }

        return $results;
    }
}
