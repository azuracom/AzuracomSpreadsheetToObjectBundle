<?php

namespace Azuracom\SpreadsheetToObject\DataTransformer;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\DataTransformerInterface;

class AttributeValueTransformer implements DataTransformerInterface
{
    private $defaultLocale;
    private $factory;
    private $attribute;
    private $innerTransformer;

    public function __construct(
        FactoryInterface $factory,
        AttributeInterface $attribute,
        ?DataTransformerInterface $innerTransformer,
        $defaultLocale
    ) {
        $this->defaultLocale = $defaultLocale;
        $this->factory = $factory;
        $this->attribute = $attribute;
        $this->innerTransformer = $innerTransformer;
    }

    public function transform($value)
    {
        if (!$value) {
            return null;
        }

        $transformer = $this->innerTransformer;
        $attributeValue = $transformer ?  $transformer->transform($value->getValue()) : $value->getValue();

        return $attributeValue;
    }

    public function reverseTransform($value)
    {
        $attribute = $this->attribute;
        $transformer = $this->innerTransformer;

        $attributeValue = $transformer ?  $transformer->reverseTransform($value) : $value;
        $AttributeValue = $this->factory->createNew();
        $AttributeValue->setAttribute($attribute);
        $AttributeValue->setValue($attributeValue);
        $AttributeValue->setLocaleCode($this->defaultLocale);

        return $AttributeValue;
    }
}
