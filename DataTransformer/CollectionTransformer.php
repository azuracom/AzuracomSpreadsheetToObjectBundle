<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class CollectionTransformer implements DataTransformerInterface
{
    /** @var string */
    private $separator;

    /** @var  DataTransformerInterface|null */
    private $itemTransformer;

    /** @var  callable|null */
    private $collectionConstruct;

    public function __construct(
        string $separator,
        ?callable $collectionConstruct,
        ?DataTransformerInterface $itemTransformer = null
    ) {
        $this->itemTransformer = $itemTransformer;
        $this->separator = $separator;
        $this->collectionConstruct = $collectionConstruct;
    }

    public function transform($collection)
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


    public function reverseTransform($string)
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
