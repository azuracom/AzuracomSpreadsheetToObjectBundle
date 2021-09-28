<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\DataTransformerInterface;
use Azuracom\SpreadsheetToObject\Spreadsheet\RowHandler;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface ColumnTypeInterface
{
    public function getName(): string;
    public function setName(string $name): self;
    public function configureOptions(OptionsResolver $resolver);
    public function getOption(string $name, $defaultValue = null);
    public function getLabel(): string;
    public function getColumn(): string;
    public function setColumn(string $column): self;
    public function getValue($transformation = 'reverseTransform');
    public function setValue($value): self;
    public function getDefaultTransformer($options): ?DataTransformerInterface;
    public function dataCanBeUpdated($data): bool;
    public function isDataMapped($data): bool;
    public function getDataValue($data, bool $transformed = true);
    public function setDataValue($data, $value);
    public function hasChanged($newValue, $oldValue): bool;
    public function hasChangedInner($newValue, $oldValue): bool;
    public function getOwner() : ?RowHandler;
    public function setOwner(RowHandler $rowHandler);
    public function addTransformer(DataTransformerInterface $transformer, $forceAppend = false): self;
    public function resetModelTransformers(): self;
    public static function getPrefix(): string;
}
