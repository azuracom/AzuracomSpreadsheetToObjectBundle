<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;


use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTimeTransformer implements DataTransformerInterface
{
    private $format;
    private $time;

    public function __construct(string $format,$time = true)
    {
        $this->format = $format;  
        $this->time = $time;  
    }

    public function transform($datetime)
    {
        return $datetime ? $datetime->format($this->format) : null;
    }

    public function reverseTransform($value)
    {
        if($value === null){
            return null;
        } 
        
        $value = \DateTime::createFromFormat($this->format, $value);
        if($value === false){
            throw new TransformationFailedException("Impossible de convertir cette valeur en date");
        }

        if(!$this->time){
            $value->setTime(0,0,0);
        }

        return $value;
    }
}