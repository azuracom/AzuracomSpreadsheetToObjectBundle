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
        ]);

        $resolver->setAllowedTypes('choices','array');
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        $choices = isset($options['choices']) ? $options['choices'] : [] ;
        return new CallbackTransformer(
            function($value) use ($choices){
                if($value === null){
                    return null;
                }

                return array_search($value,$choices);
            },
            function($value) use ($choices) {
                if($value === null){
                    return null;
                }
                
                if(!array_key_exists((string)$value,$choices)){
                    throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.choice_value_not_found", 0, null, null, [
                        '%value%' => (string) $value,
                    ]);
                }

                return $choices[(string)$value];
            }
        );
    }
}