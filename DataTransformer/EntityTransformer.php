<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityTransformer implements DataTransformerInterface
{
    private $items = null;

    private $property;

    private $repository;

    private $repositoryMethod;

    public function __construct(EntityRepository $repository,string $property,string $repositoryMethod = 'findAll')
    {
        $this->repository = $repository;
        $this->property = $property;
        $this->repositoryMethod = $repositoryMethod;
    }

    public function transform($value)
    {
        if($value === null){
            return null;
        }

        return $value->{"get".$this->property}();
    }

    public function reverseTransform($value)
    {
        if($value === null){
            return null;
        }

        $this->iniItems();
        $key = self::toKey($value);
        if(isset($this->items[$key])){
            return $this->items[$key];
        }
        
        throw new TransformationFailedException(sprintf("azuracom_spreadsheet_to_object.data_transformer_exception.entity",$value));
    }

    protected function iniItems()
    {
        if($this->items === null){
            $results = $this->repository->{$this->repositoryMethod}();
            foreach($results as $result){
                $this->items[self::toKey($result->{"get".$this->property}())] = $result;
            }
        }
    }

    public static function toKey($value)
    {
        return trim(strtolower($value));
    }
}