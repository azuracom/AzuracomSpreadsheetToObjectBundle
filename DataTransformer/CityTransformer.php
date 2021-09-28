<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use App\Util\DataMatcher;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CityTransformer implements DataTransformerInterface
{

    private $separator;
    private $dataKey;
    private $dataMatcher;

    public function __construct($separator = ":")
    {
        $this->separator = $separator;
    }

    public function configureDataMatcher(DataMatcher $dataMatcher,$dataKey)
    {
        $this->dataMatcher = $dataMatcher;
        $this->dataKey =$dataKey;

        return $this;
    }

    public function transform($city)
    {
        return $city ? $city->getPostCode() . $this->separator . $city->getName() : null;
    }

    public function reverseTransform($string)
    {
        $explode = explode($this->separator, $string);
        $postCode = trim($explode[0]);
        $cityName = isset($explode[1]) ? trim($explode[1]) : '';
        
        $city = $this->dataMatcher->findData($this->dataKey, ['postCodeAndName' => [$postCode,$cityName]]);

        if (!$city) {
            throw new TransformationFailedException(sprintf("Ville %s %s introuvable", $postCode, $cityName));
        }

        return $city;
    }
}
