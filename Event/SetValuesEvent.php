<?php

namespace Azuracom\SpreadsheetToObject\Event;

use Azuracom\SpreadsheetToObject\Spreadsheet\RowHandler;
use Symfony\Contracts\EventDispatcher\Event;

class SetValuesEvent extends Event
{
    protected $data;

    /** @var RowHandler */
    private $rowHandler;

    public function __construct(RowHandler $rowHandler,$data)
    {
        $this->rowHandler = $rowHandler;
        $this->data = $data;
    }

    /**
     * Get the value of rowHandler
     */ 
    public function getRowHandler() : RowHandler
    {
        return $this->rowHandler;
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
}