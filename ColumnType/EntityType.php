<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\EntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'find_callback' => null,
            'find_method' => 'findAll',
            'find_arguments' => [],
            'property' => null,
            'create_if_not_found' => false,
            'create_callback' => null,
        ]);

        $resolver->setRequired(['class']);
        $resolver->setAllowedTypes('class', 'string');
        $resolver->setAllowedTypes('find_method', 'string');
        $resolver->setAllowedTypes('property', ['string', 'callable', 'null']);
        $resolver->setAllowedTypes('find_callback', ['null', 'callable']);
        $resolver->setAllowedTypes('find_arguments', 'array');
        $resolver->setAllowedTypes('create_if_not_found', 'boolean');
        $resolver->setAllowedTypes('create_callback', ['null', 'callable']);
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new EntityTransformer(
            $this->em->getRepository($options['class']),
            $options['property'],
            $options['find_callback'],
            $options['find_method'],
            $options['find_arguments'],
            $options['create_if_not_found'],
            $options['create_callback']
        );
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        return $newValue->getId() !== $oldValue->getId();
    }
}
