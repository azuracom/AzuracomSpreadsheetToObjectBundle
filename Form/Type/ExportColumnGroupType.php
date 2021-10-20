<?php

namespace Azuracom\SpreadsheetToObject\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportColumnGroupType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('check', CheckboxType::class,[
                'label' => $options['label'],
                'required' => false,
            ]);
        $subBuilder = $builder->getFormFactory()->createNamedBuilder('children', $options['entry_type'], null, [
            'column_key' => $options['column_key'],
            'label'=> false,
        ]);
        $builder->add($subBuilder);
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('label','Check all');
        $resolver->setRequired(['column_key', 'entry_type']);
        $resolver->setAllowedTypes('entry_type', 'string');
        $resolver->setAllowedTypes('column_key', ['string', 'null']);
    }
}
