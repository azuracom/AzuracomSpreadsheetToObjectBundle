<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
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

    private $createIfNotFound;

    private $createCallback;

    private $queryBuilderCallback;

    private $columnType;

    public function __construct(
        EntityRepository $repository,
        $property = null,
        ?callable $findCallback = null,
        ?string $findMethod = 'findAll',
        array $findArguments = [],
        ?callable $queryBuilderCallback = null,
        bool $createIfNotFound = false,
        ?callable $createCallback = null
    ) {
        $this->repository = $repository;
        $this->property = $property;
        $this->findCallback = $findCallback;
        $this->findMethod = $findMethod;
        $this->findArguments = $findArguments;
        $this->createIfNotFound = $createIfNotFound;
        $this->createCallback = $createCallback;
        $this->queryBuilderCallback = $queryBuilderCallback;
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
            $result = call_user_func_array($this->findCallback, [$value, $this->repository, $this]);
        } else {
            $this->iniItems();
            $key = self::toKey($value);
            if (isset($this->items[$key])) {
                $result = $this->items[$key];
            }
        }


        if (!$result) {
            if (!$this->createIfNotFound) {
                throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.entity", 0, null, null, [
                    '%value%' => $value
                ]);
            } else {
                $className = $this->repository->getClassName();
                $result = new $className();
                if ($this->createCallback) {
                    call_user_func_array($this->createCallback, [$result, $value, $this]);
                }
            }
        }

        return $result;
    }

    protected function iniItems()
    {
        if ($this->items === null) {

            if ($cb = $this->queryBuilderCallback) {
                $results = $cb($this->repository)->getQuery()->getResult();
            } else {
                $results = call_user_func_array([$this->repository, $this->findMethod], $this->findArguments);
            }

            foreach ($results as $result) {
                $this->items[self::toKey($this->getProperty($result))] = $result;
            }
        }
    }

    public static function toKey($value)
    {
        return trim(strtolower($value));
    }

    public function getColumnType(): ?ColumnTypeInterface
    {
        return $this->columnType;
    }


    public function setColumnType(?ColumnTypeInterface $columnType = null): self
    {
        $this->columnType = $columnType;

        return $this;
    }
}
