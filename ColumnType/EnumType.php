<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\EnumTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnumType extends AbstractType
{
    private $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver
            ->setDefaults([
                'not_found_message' => "azuracom_spreadsheet_to_object.data_transformer_exception.choice_value_not_found",
                'enum_label' => function ($enum) {
                    $label =  method_exists($enum, 'label') ? $enum->label() : $enum->name;
                    if ($this->translator) {
                        $label = $this->translator->trans($label);
                    }

                    return $label;
                },
            ])
            ->setRequired(['class'])
            ->setAllowedTypes('class', 'string')
            ->setAllowedValues('class', \Closure::fromCallable('enum_exists'));
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new EnumTransformer($options['class'], $options['enum_label'], $options['not_found_message']);
    }
}
