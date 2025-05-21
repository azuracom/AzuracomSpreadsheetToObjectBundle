<?php

namespace Azuracom\SpreadsheetToObjectBundle\Event;

use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\HandlerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SetValuesEvent extends Event
{

    public function __construct(
        protected HandlerInterface $handler,
        protected mixed $data
    ) {
        $this->handler = $handler;
        $this->data = $data;
    }

    /**
     * Get the value of Handler
     */
    public function getHandler(): HandlerInterface
    {
        return $this->handler;
    }

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
    public function setData($data): static
    {
        $this->data = $data;

        return $this;
    }
}
