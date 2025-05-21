<?php

namespace Azuracom\SpreadsheetToObjectBundle\Form\Type;

use Azuracom\SpreadsheetToObjectBundle\CellType\TextCell;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportColumnCheckboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'cell_type' => TextCell::class,
            'cell_options' => [],
            'cell_key' => null,
            'cell_name' => null,
        ]);
        $resolver->setAllowedTypes('cell_type', 'string');
        $resolver->setAllowedTypes('cell_name', ['string', 'null']);
        $resolver->setAllowedTypes('cell_options', 'array');
        $resolver->setAllowedTypes('cell_key', ['string', 'null']);
    }
}
