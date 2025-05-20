<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;

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
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.datetime");
        }

        if(!$this->time){
            $value->setTime(0,0,0);
        }

        return $value;
    }
}