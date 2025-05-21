<?php

namespace Azuracom\SpreadsheetToObjectBundle;

use Azuracom\SpreadsheetToObjectBundle\CellType\CellTypeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AzuracomSpreadsheetToObjectBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        //add tag to all class that implements HandlerInterface
        $builder->registerForAutoconfiguration(CellTypeInterface::class)
            ->addTag("azuracom_spresheet_to_object.cell_type");

    }
}
