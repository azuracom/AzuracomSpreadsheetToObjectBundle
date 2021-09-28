<?php

namespace Azuracom\SpreadsheetToObject;

use Azuracom\SpreadsheetToObject\DependencyInjection\Compiler\ColumnTypePass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AzuracomSpreadsheetToObjectBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ColumnTypePass());
    }
}
