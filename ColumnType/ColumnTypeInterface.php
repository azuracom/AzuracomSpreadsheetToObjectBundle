<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface ColumnTypeInterface
{
    public function init(string $name, array $options = []);
    public function getName(): string;
    public function setName(string $name): self;
    public function configureOptions(OptionsResolver $resolver);
    public function getOption(string $name, $defaultValue = null);
    public function getLabel(): string;
    public function getColumn(): string;
    public function getRow(): ?int;
    public function getValue($transformation = 'reverseTransform');
    public function setValue($value): self;
    public function getDefaultTransformer($options): ?DataTransformerInterface;
    public function dataCanBeUpdated($data): bool;
    public function isDataMapped($data, string $key): bool;
    public function getDataValue($data, bool $transformed = true);
    public function setDataValue($data, $value);
    public function hasChanged($newValue, $oldValue): bool;
    public function hasChangedInner($newValue, $oldValue): bool;
    public function getOwner(): ?HandlerInterface;
    public function setOwner(HandlerInterface $Handler);
    public function addTransformer(DataTransformerInterface $transformer, $forceAppend = false): self;
    public function resetModelTransformers(): self;
    public static function getPrefix(): string;
}
