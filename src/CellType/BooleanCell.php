<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\BooleanTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanCell extends AbstractCell
{
    public function configureOptions(OptionsResolver $resolver): void
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
