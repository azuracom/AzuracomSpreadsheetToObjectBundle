<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\DateTimeTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeCell extends AbstractCell
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'format' => 'Y-m-d H:i:s',
        ]);

        $resolver->setAllowedTypes('format','string');
    }
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new DateTimeTransformer($this->getOption('format'));
    }
}