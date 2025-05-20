<?php

namespace Azuracom\SpreadsheetToObjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportColumnGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('check', CheckboxType::class, [
                'label' => $options['label'],
                'required' => false,
            ])
            ->add('children', $options['entry_type'], $options['entry_options']);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined('entry_type')
            ->setRequired('entry_type')
            ->setAllowedTypes('entry_type', 'string');

        $resolver
            ->setDefault('column_key', null)
            ->setAllowedTypes('column_key', ['string', 'null']);

        $resolver
            ->setDefault('entry_options', [
                'label' => false,
            ])
            ->setAllowedTypes('entry_options', 'array')
            ->setNormalizer('entry_options',  function (Options $options, $value) {
                return array_merge([
                    'column_key' => $options['column_key'],
                ], $value);
            });
    }
}
