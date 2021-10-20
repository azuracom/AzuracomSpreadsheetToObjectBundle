<?php

namespace Azuracom\SpreadsheetToObject\Form\Type;

use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportColumnCheckboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => $options['label'],
            ])
            ->add('column', HiddenType::class, [
                'required' => false,
                'label' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'column_type' => TextType::class,
            'column_options' => [],
            'column_key' => null,
            'column_name' => null,
        ]);
        $resolver->setAllowedTypes('column_type', 'string');
        $resolver->setAllowedTypes('column_name', ['string', 'null']);
        $resolver->setAllowedTypes('column_options', 'array');
        $resolver->setAllowedTypes('column_key', ['string', 'null']);
    }
}
