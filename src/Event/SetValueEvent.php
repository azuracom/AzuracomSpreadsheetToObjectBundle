<?php

namespace Azuracom\SpreadsheetToObjectBundle\Event;

use Azuracom\SpreadsheetToObjectBundle\CellType\CellTypeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SetValueEvent extends Event
{

    public function __construct(
        private CellTypeInterface $cellType,
        private mixed $data,
        private mixed $value
    ) {}

    /**
     * Get the value of data
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Set the value of data
     *
     * @return  self
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of CellType
     */
    public function getCellType(): CellTypeInterface
    {
        return $this->cellType;
    }

    /**
     * Get the value of value
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the value of value
     *
     * @return  self
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }
}
