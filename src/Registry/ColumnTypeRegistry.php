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

    public function getType(string $className): ColumnTypeInterface
    {
        
        /** @var ColumnTypeInterface */
        foreach ($this->columnTypes as $type) {
            if(get_class($type) === $className) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf('Column type "%s" not found.', $className));
    }
}
