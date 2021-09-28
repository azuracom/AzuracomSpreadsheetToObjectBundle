<?php

namespace Azuracom\SpreadsheetToObject\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;

class ColumnTypePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has("azuracom.spresheet_to_object.column_type_registry")) {
            return;
        }

        $definition = $container->findDefinition("azuracom.spresheet_to_object.column_type_registry");

        $servicesMap = [];

        // Builds an array with fully-qualified type class names as keys and service IDs as values
        foreach ($container->findTaggedServiceIds("azuracom_spresheet_to_object.excel_column_type", true) as $serviceId => $tag) {
            // Add form type service to the service locator
            $serviceDefinition = $container->getDefinition($serviceId);
            $servicesMap[$serviceDefinition->getClass()] = new Reference($serviceId);
        }

        $typeContainer = ServiceLocatorTagPass::register($container, $servicesMap);

        $definition->setArgument(0, $typeContainer);
    }
}