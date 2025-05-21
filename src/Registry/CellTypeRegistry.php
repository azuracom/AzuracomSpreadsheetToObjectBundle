<?php

namespace Azuracom\SpreadsheetToObjectBundle\Registry;

use Azuracom\SpreadsheetToObjectBundle\CellType\CellTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class CellTypeRegistry
{

    public function __construct(
        /** @var CellTypeInterface */
        #[AutowireIterator('azuracom_spresheet_to_object.cell_type')]
        private iterable $CellTypes,
    ) {}

    public function getType(string $className): CellTypeInterface
    {
        
        /** @var CellTypeInterface */
        foreach ($this->CellTypes as $type) {
            if(get_class($type) === $className) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf('Column type "%s" not found.', $className));
    }
}
