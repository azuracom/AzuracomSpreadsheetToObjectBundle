<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;


class CollectionTransformer implements DataTransformerInterface
{

    private $separator;
    private $baseTransformer;

    public function __construct(DataTransformerInterface $baseTransformer, $separator = ";")
    {
        $this->baseTransformer = $baseTransformer;
        $this->separator = $separator;
    }

    public function transform($collection)
    {
        if ($collection === null || count($collection) == 0) {
            return null;
        }

        $string = "";
        foreach ($collection as $item) {
            $string .= $this->baseTransformer->transform($item) . $this->separator;
        }

        return substr($string, 0, -1);
    }


    public function reverseTransform($string)
    {
        $collection = new ArrayCollection();

        if($string){
            foreach (explode($this->separator, $string) as $value) {
                $collection->add($this->baseTransformer->reverseTransform($value));
            }
        }

        return $collection;
    }
}
