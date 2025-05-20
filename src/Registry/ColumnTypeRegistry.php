<?php

namespace Azuracom\SpreadsheetToObjectBundle\Registry;

use Psr\Container\ContainerInterface;

class ColumnTypeRegistry
{
    protected $typeContainer;
    
    public function __construct(ContainerInterface $typeContainer)
    {
        $this->typeContainer = $typeContainer;
    }

    public function getType($name)
    {
        if (!$this->typeContainer->has($name)) {
            throw new \InvalidArgumentException(sprintf('The column type "%s" is not registered in the service container.', $name));
        }

        return $this->typeContainer->get($name);
    }
}