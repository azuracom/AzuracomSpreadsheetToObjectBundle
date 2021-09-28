<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'item_equal_callback' => null,
        ]);

        $resolver->setAllowedTypes('item_equal_callback',['null','callable']);
    }

    public function hasChangedInner($newValue,$oldValue) :bool
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
                if (($callback && $callback($newItem,$oldItem)) || (!$callback && $newItem === $oldItem)) {
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