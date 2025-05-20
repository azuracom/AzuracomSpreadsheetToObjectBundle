<?php

namespace Azuracom\SpreadsheetToObjectBundle;

use Azuracom\SpreadsheetToObjectBundle\ColumnType\ColumnTypeInterface;
use Azuracom\SpreadsheetToObjectBundle\DependencyInjection\Compiler\ColumnTypePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AzuracomSpreadsheetToObjectBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        //add tag to all class that implements HandlerInterface
        $container->registerForAutoconfiguration(ColumnTypeInterface::class)
            ->addTag("azuracom_spresheet_to_object.column_type");

        $builder->addCompilerPass(new ColumnTypePass());
    }
}
