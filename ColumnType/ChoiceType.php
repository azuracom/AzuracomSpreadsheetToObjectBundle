<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceType extends AbstractType
{

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'choices' => [],
            'not_found_message' => "azuracom_spreadsheet_to_object.data_transformer_exception.choice_value_not_found",
        ]);

        $resolver->setAllowedTypes('choices', 'array');
        $resolver->setAllowedTypes('not_found_message', 'string');
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        $choices = isset($options['choices']) ? $options['choices'] : [];
        $message = $options['not_found_message'];
        return new CallbackTransformer(
            function ($value) use ($choices) {
                if ($value === null) {
                    return null;
                }

                return array_search($value, $choices);
            },
            function ($value) use ($choices, $message) {
                if ($value === null) {
                    return null;
                }

                if (!array_key_exists((string)$value, $choices)) {
                    throw new TransformationFailedException($message, 0, null, null, [
                        '%value%' => (string) $value,
                    ]);
                }

                return $choices[(string)$value];
            }
        );
    }
}
