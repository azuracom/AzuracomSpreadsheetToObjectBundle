<?php

namespace Azuracom\SpreadsheetToObjectBundle\Event;

use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\HandlerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SetValuesEvent extends Event
{
    protected $data;

    /** @var HandlerInterface */
    private $handler;

    public function __construct(HandlerInterface $handler,$data)
    {
        $this->handler = $handler;
        $this->data = $data;
    }

    /**
     * Get the value of Handler
     */ 
    public function getHandler() : HandlerInterface
    {
        return $this->handler;
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