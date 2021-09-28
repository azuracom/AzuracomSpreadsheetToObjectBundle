<?php

namespace Azuracom\SpreadsheetToObject\DependencyInjection;

use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AzuracomSpreadsheetToObjectExtension extends Extension
{
    public function load(array $config, ContainerBuilder $container)
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $config);
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.yaml');

        //add tag to all class that implements HandlerInterface
        $container->registerForAutoconfiguration(ColumnTypeInterface::class)
            ->addTag("azuracom_spresheet_to_object.column_type");
    }
}
