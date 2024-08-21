<?php

namespace Azuracom\SpreadsheetToObject\ColumnType;

use Azuracom\SpreadsheetToObject\DataTransformer\AttributeValueSelectValueTransformer;
use Azuracom\SpreadsheetToObject\DataTransformer\AttributeValueTransformer;
use Azuracom\SpreadsheetToObject\DataTransformer\BooleanTransformer;
use Azuracom\SpreadsheetToObject\DataTransformer\ExcelDateTimeTransformer;
use Azuracom\SpreadsheetToObject\DataTransformer\IntegerTransformer;
use Azuracom\SpreadsheetToObject\DataTransformer\PercentTransformer;
use Azuracom\SpreadsheetToObject\DataTransformer\StringTransformer;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Attribute\Model\AttributeSubjectInterface;
use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Sylius\Component\Attribute\AttributeType\DateAttributeType;
use Sylius\Component\Attribute\AttributeType\DatetimeAttributeType;
use Sylius\Component\Attribute\AttributeType\IntegerAttributeType;
use Sylius\Component\Attribute\AttributeType\PercentAttributeType;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextareaAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AttributeType extends AbstractType
{
    /** @var string */
    protected $locale;

    /** @var FactoryInterface */
    protected $factory;

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'attribute' => null,
            'inner_transformer' => [],
            'date_format_code' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'datetime_format_code' => NumberFormat::FORMAT_DATE_DATETIME,
            'create_choice_if_not_found' => false,
            'create_choice_callback' => function (mixed $choice, AttributeInterface $attribute, string $locale): string {
                $key = $this->genUuid();
                $configuration = $attribute->getConfiguration();
                $choices = $configuration['choices'] ?? [];
                $choices[$key] = [
                    $locale =>  $choice
                ];
                $configuration['choices'] = $choices;
                $attribute->setConfiguration($configuration);

                return $key;
            },
            'allow_null_value' => false,
            'getter' => function (Options $options) {
                /** @var AttributeInterface */
                $attribute = $options['attribute'];
                return function (AttributeSubjectInterface $subject) use ($attribute) {
                    return $subject->getAttributeByCodeAndLocale($attribute->getCode(), $this->locale);
                };
            },
            'setter' => function (Options $options) {
                /** @var AttributeInterface */
                $attribute = $options['attribute'];
                return function (AttributeSubjectInterface $subject, $value) use ($attribute) {

                    if ($attributeValue = $subject->getAttributeByCodeAndLocale($attribute->getCode(), $this->locale)) {
                        if ($value !== null && $value->getValue() !== null) {
                            $attributeValue->setValue($value->getValue());
                        } else {
                            $subject->removeAttribute($attributeValue);
                        }
                    } elseif ($value !== null) {
                        $subject->addAttribute($value);
                    }
                };
            },
            'cell_styles' => function (Options $options) {
                /** @var AttributeInterface */
                $attribute = $options['attribute'];
                return function () use ($attribute, $options) {
                    $formatCode = null;
                    switch ($attribute->getType()) {
                        case DateAttributeType::TYPE:
                            $formatCode = $options['date_format_code'];
                            break;
                        case DatetimeAttributeType::TYPE:
                            $formatCode = $options['datetime_format_code'];
                            break;
                    }

                    if ($formatCode) {
                        return [
                            'numberFormat' => [
                                'formatCode' => $formatCode
                            ]
                        ];
                    }

                    return [];
                };
            },
        ]);

        $resolver->setAllowedTypes('allow_null_value', 'boolean');
        $resolver->setAllowedTypes('date_format_code', 'string');
        $resolver->setAllowedTypes('datetime_format_code', 'string');
        $resolver->setAllowedTypes('attribute', AttributeInterface::class);
        $resolver->setAllowedTypes('inner_transformer', DataTransformerInterface::class . '[]');
        $resolver->setAllowedTypes('create_choice_callback', ['null', 'callable']);
        $resolver->setAllowedTypes('create_choice_if_not_found', 'boolean');
        $resolver->setRequired('attribute');
    }

    public static function genUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public function getDefaultInnerTransformer(AttributeInterface $attribute, array $options = [])
    {
        switch ($attribute->getType()) {
            case CheckboxAttributeType::TYPE:
                return (new BooleanTransformer(['yes'], ['no']));
            case IntegerAttributeType::TYPE:
                return (new IntegerTransformer());
            case PercentAttributeType::TYPE:
                return (new PercentTransformer());
            case DateAttributeType::TYPE:
                return (new ExcelDateTimeTransformer());
            case DatetimeAttributeType::TYPE:
                return (new ExcelDateTimeTransformer());
            case SelectAttributeType::TYPE:
                return (new AttributeValueSelectValueTransformer(
                    $attribute,
                    $this->locale,
                    ',',
                    false,
                    $options['create_choice_if_not_found'] ? $options['create_choice_callback'] : null
                ));
            case TextareaAttributeType::TYPE:
            case TextAttributeType::TYPE:
                return (new StringTransformer());
        }

        return null;
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        /** @var AttributeInterface */
        $attribute = $options['attribute'];
        $innerTransformer = isset($options['inner_transformer'][$attribute->getType()]) ?
            $options['inner_transformer'][$attribute->getType()] :
            $this->getDefaultInnerTransformer($attribute, $options);
        return new AttributeValueTransformer($this->factory, $attribute, $innerTransformer, $this->locale, $options['allow_null_value']);
    }
}
