<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObject\Exception\TransformationFailedException;
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
                return array_search($value,$choices);
            },
            function($value) use ($choices) {
                if(!array_key_exists((string)$value,$choices)){
                    throw new TransformationFailedException("Cette valeur ne fait pas partie de la liste");
                }

                return $choices[(string)$value];
            }
        );
    }
}