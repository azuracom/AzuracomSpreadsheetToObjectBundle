<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\DateTimeTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateTimeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'format' => 'Y-m-d H:i:s',
        ]);

        $resolver->setAllowedType('format','string');
    }
    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new DateTimeTransformer($this->getOption('format'));
    }
}