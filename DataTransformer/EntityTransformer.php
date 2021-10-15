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

    private $findCallback;

    private $findMethod;

    private $findArguments;

    public function __construct(
        EntityRepository $repository,
        $property,
        ?callable $findCallback = null,
        string $findMethod,
        array $findArguments
    ) {
        $this->repository = $repository;
        $this->property = $property;
        $this->findCallback = $findCallback;
        $this->findMethod = $findMethod;
        $this->findArguments = $findArguments;
    }

    public function transform($value)
    {
        if ($value === null) {
            return null;
        }

        return $this->getProperty($value);
    }

    public function getProperty($entity)
    {
        if (is_string($this->property)) {
            return $entity->{"get" . $this->property}();
        }

        return call_user_func_array($this->property, [$entity]);
    }

    public function reverseTransform($value)
    {
        if ($value === null) {
            return null;
        }

        $result = null;
        if ($this->findCallback) {
            $result = call_user_func_array($this->findCallback, [$value,$this->repository]);
        } else {
            $this->iniItems();
            $key = self::toKey($value);
            if (isset($this->items[$key])) {
                $result = $this->items[$key];
            }
        }


        if (!$result) {
            throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.entity", 0, null, null, [
                '%value%' => $value
            ]);
        }

        return $result;
    }

    protected function iniItems()
    {
        if ($this->items === null) {
            $results = call_user_func_array([$this->repository, $this->findMethod], $this->findArguments);
            foreach ($results as $result) {
                $this->items[self::toKey($this->getProperty($result))] = $result;
            }
        }
    }

    public static function toKey($value)
    {
        return trim(strtolower($value));
    }
}
