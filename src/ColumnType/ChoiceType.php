<?php

namespace Azuracom\SpreadsheetToObjectBundle\ColumnType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\ChoiceTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'choices' => [],
            'not_found_message' => "azuracom_spreadsheet_to_object.data_transformer_exception.choice_value_not_found",
            'not_found_message_show_values' => true,
            'case_sensitive' => true,
        ]);

        $resolver->setAllowedTypes('choices', 'array');
        $resolver->setAllowedTypes('not_found_message', 'string');
        $resolver->setAllowedTypes('not_found_message_show_values', 'boolean');
        $resolver->setAllowedTypes('case_sensitive', 'boolean');
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new ChoiceTransformer(
            $options['choices'],
            $options['not_found_message'],
            $options['not_found_message_show_values'],
            $options['case_sensitive']
        );
    }
}
