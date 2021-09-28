<?php

namespace Azuracom\SpreadsheetToObject\Event;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SetValueEvent extends Event
{
    protected $data;

    protected $value;

    /** @var ColumnTypeInterface */
    private $columnType;

    public function __construct(ColumnTypeInterface $columnType,$data,$value)
    {
        $this->columnType = $columnType;
        $this->data = $data;
        $this->value = $value;
    }

    /**
     * Get the value of data
     */ 
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of data
     *
     * @return  self
     */ 
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of columnType
     */ 
    public function getColumnType()
    {
        return $this->columnType;
    }

    /**
     * Get the value of value
     */ 
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of value
     *
     * @return  self
     */ 
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}