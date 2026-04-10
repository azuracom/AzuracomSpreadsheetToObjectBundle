<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Azuracom\SpreadsheetToObjectBundle\CellType\CellTypeInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityTransformer implements DataTransformerInterface
{
    private ?array $items = null;
    private ?CellTypeInterface $cellType;
    private ?\Closure $findCallback = null;
    private ?\Closure $queryBuilderCallback = null;
    private ?\Closure $createCallback = null;
    private \Closure|string|null $property = null;

    public function __construct(
        private EntityRepository $repository,
        callable|string|null $property = null,
        ?callable $findCallback = null,
        private ?string $findMethod = 'findAll',
        private array $findArguments = [],
        ?callable $queryBuilderCallback = null,
        private bool $createIfNotFound = false,
        ?callable $createCallback = null
    ) {
        if ($findCallback) {
            $this->findCallback = \Closure::fromCallable($findCallback);
        }

        if ($queryBuilderCallback) {
            $this->queryBuilderCallback = \Closure::fromCallable($queryBuilderCallback);
        }

        if ($createCallback) {
            $this->createCallback = \Closure::fromCallable($createCallback);
        }

        if($property && !is_string($property)) {
            $this->property = \Closure::fromCallable($property);
        } else {
            $this->property = $property;
        }


    }

    public function transform(mixed $value): mixed
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

    public function reverseTransform(mixed $value): mixed
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
                $result->{"set" . $this->property}($value);
                if ($this->createCallback) {
                    $createResult = call_user_func_array($this->createCallback, [$result, $value, $this]);
                    if ($createResult === false) {
                        throw new TransformationFailedException("azuracom_spreadsheet_to_object.data_transformer_exception.entity", 0, null, null, [
                            '%value%' => $value
                        ]);
                    }
                }

                // Add to items to avoid multiple creation if the same value appears multiple times in the spreadsheet
                $this->repository->getEntityManager()->persist($result);
                $this->items[self::toKey($value)] = $result;
            }
        }

        return $result;
    }

    protected function iniItems(): void
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

    public static function toKey(string $value): string
    {
        return trim(strtolower($value));
    }

    public function getCellType(): ?CellTypeInterface
    {
        return $this->cellType;
    }


    public function setCellType(?CellTypeInterface $cellType = null): self
    {
        $this->cellType = $cellType;

        return $this;
    }
}
