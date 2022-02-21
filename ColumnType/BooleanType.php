<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\BooleanTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'true_values' => [1, '1', 'yes', 'y', 'oui'],
            'false_values' =>  [0, '0', 'no', 'n'],
        ]);

        $resolver->setAllowedTypes('true_values', 'array');
        $resolver->setAllowedTypes('false_values', 'array');
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new BooleanTransformer(
            $options['true_values'],
            $options['false_values']
        );
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        return (int) $newValue !== (int) $oldValue;
    }
}
