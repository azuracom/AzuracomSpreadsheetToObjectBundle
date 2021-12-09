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
    private $allowNullValue;

    public function __construct(
        FactoryInterface $factory,
        AttributeInterface $attribute,
        ?DataTransformerInterface $innerTransformer,
        string $defaultLocale,
        bool $allowNullValue = false,
    ) {
        $this->defaultLocale = $defaultLocale;
        $this->factory = $factory;
        $this->attribute = $attribute;
        $this->innerTransformer = $innerTransformer;
        $this->allowNullValue = $allowNullValue;
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
