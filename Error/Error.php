<?php

namespace Azuracom\SpreadsheetToObject\Error;

class Error
{
    private $message;
    private $code;
    private $row;
    private $column;

    public function __construct(string $message, $code = null,$row = null,$column = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->row = $row;
        $this->column = $column;
    }

    /**
     * Get the value of message
     */ 
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the value of message
     *
     * @return  self
     */ 
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of code
     */ 
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the value of code
     *
     * @return  self
     */ 
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of row
     */ 
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Set the value of row
     *
     * @return  self
     */ 
    public function setRow($row)
    {
        $this->row = $row;

        return $this;
    }

    /**
     * Get the value of column
     */ 
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set the value of column
     *
     * @return  self
     */ 
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }
}