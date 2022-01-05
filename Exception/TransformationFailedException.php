<?php

namespace Azuracom\SpreadsheetToObject\Exception;

use Symfony\Component\Form\Exception\TransformationFailedException as BaseException;

class TransformationFailedException extends BaseException
{
    /** @var array  */
    private $invalidMessageParameters;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null,$invalidMessageParameters = [])
    {
        parent::__construct($message,$code,$previous);
        $this->invalidMessageParameters = $invalidMessageParameters;
    }

    public function getInvalidMessageParameters()
    {
        return $this->invalidMessageParameters;
    }
} 