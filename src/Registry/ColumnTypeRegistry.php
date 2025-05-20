<?php

namespace Azuracom\SpreadsheetToObjectBundle\Registry;

use Azuracom\SpreadsheetToObjectBundle\ColumnType\ColumnTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class ColumnTypeRegistry
{

    public function __construct(
        /** @var ColumnTypeInterface */
        #[AutowireIterator('azuracom_spresheet_to_object.column_type')]
        private iterable $columnTypes,
    ) {}

    public function getType($name): ColumnTypeInterface
    {
        /** @var ColumnTypeInterface */
        foreach ($this->columnTypes as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf('Column type "%s" not found.', $name));
    }
}
