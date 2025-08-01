<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class CollectionTransformer implements DataTransformerInterface
{

    private ?\Closure $collectionConstruct = null;

    public function __construct(
        private string $separator,
        ?callable $collectionConstruct,
        private ?DataTransformerInterface $itemTransformer = null
    ) {
        if ($collectionConstruct) {
            $this->collectionConstruct = \Closure::fromCallable($collectionConstruct);
        }
    }

    public function transform(mixed $collection): mixed
    {
        if ($collection === null || count($collection) == 0) {
            return null;
        }

        $string = "";
        foreach ($collection as $item) {
            $itemString = $this->itemTransformer ? $this->itemTransformer->transform($item) : (string) $item;
            $string .= $itemString . $this->separator;
        }

        return substr($string, 0, -1);
    }


    public function reverseTransform(mixed $string): mixed
    {
        $construct = $this->collectionConstruct;
        $collection = $construct ? $construct() : [];
        if ($string) {
            foreach (explode($this->separator, $string) as $value) {
                $value = trim($value);
                $value = $this->itemTransformer ? $this->itemTransformer->reverseTransform($value) : $value;
                $collection[] = $value;
            }
        }

        return $collection;
    }
}
