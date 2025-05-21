<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\HandlerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface CellTypeInterface
{
    public function init(string $name, array $options = []): void;
    public function getName(): string;
    public function setName(string $name): static;
    public function configureOptions(OptionsResolver $resolver): void;
    public function getOption(string $name, mixed $defaultValue = null): mixed;
    public function getLabel(): string;
    public function getColumn(): string;
    public function getRow(): ?int;
    public function getValue(?string $transformation = 'reverseTransform'): mixed;
    public function setValue($value): static;
    public function getDefaultTransformer($options): ?DataTransformerInterface;
    public function dataCanBeUpdated(mixed $data, mixed $newValue, mixed $oldValue): bool;
    public function isDataMapped(mixed $data, string $key): bool;
    public function getDataValue(mixed $data, bool $transformed = true): mixed;
    public function setDataValue(mixed &$data, mixed $value): mixed;
    public function hasChanged(mixed $newValue, mixed $oldValue): bool;
    public function hasChangedInner(mixed $newValue, mixed $oldValue): bool;
    public function getOwner(): ?HandlerInterface;
    public function setOwner(HandlerInterface $Handler): static;
    public function addTransformer(DataTransformerInterface $transformer, $forceAppend = false): static;
    public function resetModelTransformers(): static;
    public static function getPrefix(): string;
    public function resetValues(): static;
}
