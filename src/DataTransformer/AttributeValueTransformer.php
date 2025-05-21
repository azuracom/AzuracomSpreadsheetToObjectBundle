<?php

namespace Azuracom\SpreadsheetToObjectBundle\DataTransformer;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\DataTransformerInterface;

class AttributeValueTransformer implements DataTransformerInterface
{
    public function __construct(
        private FactoryInterface $factory,
        private AttributeInterface $attribute,
        private ?DataTransformerInterface $innerTransformer,
        private string $defaultLocale,
        private bool $allowNullValue = false
    ) {
    }

    public function transform(mixed $value): mixed
    {
        if (!$value) {
            return null;
        }

        $transformer = $this->innerTransformer;
        $attributeValue = $transformer ?  $transformer->transform($value->getValue()) : $value->getValue();

        return $attributeValue;
    }

    public function reverseTransform(mixed $value): mixed
    {

        $attribute = $this->attribute;
        $transformer = $this->innerTransformer;

        $attributeValue = $transformer ?  $transformer->reverseTransform($value) : $value;
        if ($attributeValue === null && !$this->allowNullValue) {
            return null;
        }

        $AttributeValue = $this->factory->createNew();
        $AttributeValue->setAttribute($attribute);
        $AttributeValue->setValue($attributeValue);
        $AttributeValue->setLocaleCode($this->defaultLocale);

        return $AttributeValue;
    }
}
