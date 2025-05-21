<?php

namespace Azuracom\SpreadsheetToObjectBundle\Error;

class Error
{
    public function __construct(
        private string $message,
        private ?string $code = null,
        private ?int $row = null,
        private ?string $column = null
    ) {}

    /**
     * Get the value of message
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set the value of message
     *
     * @return  self
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get the value of code
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set the value of code
     *
     * @return  self
     */
    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get the value of row
     */
    public function getRow(): ?int
    {
        return $this->row;
    }

    /**
     * Set the value of row
     *
     * @return  self
     */
    public function setRow(?int $row): static
    {
        $this->row = $row;

        return $this;
    }

    /**
     * Get the value of column
     */
    public function getColumn(): ?string
    {
        return $this->column;
    }

    /**
     * Set the value of column
     *
     * @return  self
     */
    public function setColumn(?string $column): static
    {
        $this->column = $column;

        return $this;
    }
}
