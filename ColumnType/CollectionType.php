<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\CollectionTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'item_equal_callback' => null, //callback method to compare if item is equal
            'separator' => ",", //Separator in cell
            'item_transformer' => null, //a tranform to apply on each collection item
            'collection_class' => null, //call of the collection, should be callable has an array with $collection[] = 'item'
            //callback to create te collection
            'collection_construct' => function (Options $options) {
                $className = $options['collection_class'];
                return function () use ($className) {
                    if ($className === null) {
                        return [];
                    }

                    return new $className();
                };
            }
        ]);

        $resolver->setAllowedTypes('separator', 'string');
        $resolver->setAllowedTypes('collection_class', ['null', 'string']);
        $resolver->setAllowedTypes('item_transformer', ['null', DataTransformerInterface::class]);
        $resolver->setAllowedTypes('collection_construct', 'callable');
        $resolver->setAllowedTypes('item_equal_callback', ['null', 'callable']);
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new CollectionTransformer($options['separator'], $options['collection_construct'], $options['item_transformer']);
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        $count1 = count($newValue);
        $count2 = count($oldValue);

        if ($count1 != $count2) {
            return true;
        }

        $callback = $this->getOption('item_equal_callback');
        foreach ($newValue as $newItem) {
            $found = false;
            foreach ($oldValue as $oldItem) {
                if (($callback && $callback($newItem, $oldItem)) || (!$callback && $newItem === $oldItem)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return true;
            }
        }

        return false;
    }
}
