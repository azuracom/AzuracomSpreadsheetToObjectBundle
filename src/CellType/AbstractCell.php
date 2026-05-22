<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Symfony\Component\Form\DataTransformerInterface;
use Azuracom\SpreadsheetToObjectBundle\Spreadsheet\HandlerInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;

abstract class AbstractCell implements CellTypeInterface
{
    const ACCESSOR_DEFAULT = 'accessor_default';
    const ACCESSOR_MANUAL = 'accessor_manual';
    const ACCESSOR_CALLBACK = 'accessor_callback';
    const ACCESSOR_USER_FUNC_ARRAY = 'accessor_user_func_array';

    /** @var string */
    protected $name;

    /** @var mixed */
    protected $owner;

    /** @var mixed */
    protected $value;

    /** @var mixed */
    protected $transformedValue;

    /** @var mixed */
    protected $reverseTransformedValue;

    /** @var array */
    protected $options;

    /** @var string */
    protected $setterType;

    /** @var string */
    protected $getterType;

    protected $propertyAccessor;

    /** @var DataTransformerInterface[] */
    protected $transformers;

    /**
     * @var string $column excel column (ex: A, AF,ZZZ)
     * @var string $name an unique name to identifiy this
     */
    public function init(string $name, array $options = []): void
    {
        $this->name = $name;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->resetModelTransformers();
        if ($transformer = $this->getDefaultTransformer($this->options)) {
            $this->addTransformer($transformer);
        }

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        //default values
        $resolver->setDefaults([
            //label used for create excel header
            'label' => null,
            // If defined the label will be translated with this translation domain
            'label_translation_domain' => null,
            /*
            null: reuse name to create a setter (name = "some.randome.name.code",methode "setCode" will be called)
            callable: use a callback function to modify data with arguments($data,$this)
            array: key 0 is data method key 1 is an array for arguments ['setToto',[$args,'@']] use '@' to replace with cell value
            string: manually define the method to call
            false: ignored
            */
            'setter' => null,
            'getter' => null,
            //set if the data value can be updated
            'allow_update' => true,
            //Add an error if allow_update = false and data has changer
            'allow_update_error' => false,
            //Add constraint to validate transformed value
            'constraints' => [],
            //add regex to add error on this config
            'error_match_path' => null,
            //some info to display to the user
            'help' => null,
            //set if help is a template to more advanced info to display
            'help_is_html' => false,
            'has_changed_callback' => null,
            //the data to return if no data found in cell
            'empty_data' => null,
            //the key to define if an object is mapped to a column
            'key' => null,

            'transformation_error_code' => 0,
            //cell coordinate stuff
            'row' => null,
            'column' => null,
            'cell' => null,
            //apply styl on cell for export
            'cell_styles' => null,
            //retrieve cell object for export
            'cell_callback' => null,
            //set column width for export
            'column_width' => null,
            //set column comment for export
            'column_comment' => null,
            'column_comment_width' => null,
            // Define if the value of the cell should be calculated with PhpSpreadsheet or not. If false, the raw value will be used (ex: formula will not be calculated and will be returned as string)
            // This can be defined for each cell or globally on the Handler with setUseCalculatedValue method
            // Cell configuration will have priority over global configuration
            'use_calculated_value' => null,
        ]);

        //validation
        $resolver->setAllowedTypes('label', ['string', 'null']);
        $resolver->setAllowedTypes('setter', ['null', 'string', 'array', 'callable', 'boolean']);
        $resolver->setAllowedTypes('getter', ['null', 'string', 'array', 'callable', 'boolean']);
        $resolver->setAllowedTypes('constraints', [Constraint::class . '[]']);
        $resolver->setAllowedTypes('allow_update', ['boolean', 'callable']);
        $resolver->setAllowedTypes('allow_update_error', ['boolean']);
        $resolver->setAllowedTypes('error_match_path', ['string', 'null']);
        $resolver->setAllowedTypes('help', ['string', 'null']);
        $resolver->setAllowedTypes('has_changed_callback', ['null', 'callable']);
        $resolver->setAllowedTypes('row', ['null', 'int']);
        $resolver->setAllowedTypes('column', ['null', 'string', 'int']);
        $resolver->setAllowedTypes('cell', ['null', 'string']);
        $resolver->setAllowedTypes('transformation_error_code', ['int']);
        $resolver->setAllowedTypes('cell_styles', ['null', 'array', 'callable']);
        $resolver->setAllowedTypes('column_width', ['null', 'int']);
        $resolver->setAllowedTypes('column_comment', ['null', 'string']);
        $resolver->setAllowedTypes('column_comment_width', ['null', 'int']);
        $resolver->setAllowedTypes('cell_callback', ['null', 'callable']);

        //normalize option
        $resolver->setNormalizer('setter', function (Options $options, $value) {
            $this->setterType = $this->guessAccessorType($value);
            return $value === null ? $this->name : $value;
        });

        $resolver->setNormalizer('getter', function (Options $options, $value) {
            $this->getterType = $this->guessAccessorType($value);
            return $value === null ? $this->name : $value;
        });

        $resolver->setNormalizer('row', function (Options $options, $value) {
            if ($value) {
                return $value;
            }

            if ($options['cell']) {
                $matches = [];
                preg_match("#\d+#", $options['cell'], $matches, PREG_OFFSET_CAPTURE);
                $offset = $matches[0][1];
                $length = strlen($options['cell']);
                return substr($options['cell'], - ($length - $offset));
            }

            return null;
        });

        $resolver->setNormalizer('column', function (Options $options, $value) {
            if (is_int($value) || preg_match('/^\d+$/', $value)) {
                return Coordinate::stringFromColumnIndex((int) $value);
            }

            if ($value) {
                return $value;
            }

            if ($options['cell']) {
                $matches = [];
                preg_match("#\d+#", $options['cell'], $matches, PREG_OFFSET_CAPTURE);
                $offset = $matches[0][1];
                return substr($options['cell'], 0, $offset);
            }

            return $this->getOwner()->getDefaultColumn();
        });

        $resolver->setNormalizer('key', function (Options $options, $value) {
            if ($value) {
                return $value;
            }

            return $this->getOwner()->getCurrentKey();
        });
    }

    public function isDataMapped(mixed $data, string $key): bool
    {
        return $this->options['key'] === $key;
    }

    public function guessAccessorType(mixed $value): ?string
    {
        if (is_null($value)) {
            return self::ACCESSOR_DEFAULT;
        } else if (is_string($value)) {
            return self::ACCESSOR_MANUAL;
        } elseif (is_array($value)) {
            return  self::ACCESSOR_USER_FUNC_ARRAY;
        } elseif (is_callable($value)) {
            return self::ACCESSOR_CALLBACK;
        }

        return null;
    }

    public function getDataValue(mixed $data, bool $transformed = true): mixed
    {
        $getter = $this->getOption('getter');

        if ($getter === false) {
            return null;
        }
        $value = null;
        switch ($this->getterType) {
            case self::ACCESSOR_DEFAULT:
                try {
                    $value = $this->propertyAccessor->getValue($data, $getter);
                } catch (\Exception $e) {
                }
                break;

            case self::ACCESSOR_MANUAL:
                $value = $data->{$getter}();
                break;

            case self::ACCESSOR_USER_FUNC_ARRAY:
                $value = call_user_func_array([$data, $getter[0]], $getter[1]);
                break;

            case self::ACCESSOR_CALLBACK:
                $value = $getter($data, $this);
                break;
        }

        if (!$transformed) {
            return $value;
        }

        foreach ($this->transformers as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }

    public function setDataValue(mixed &$data, mixed $value): mixed
    {
        $setter = $this->getOption('setter');
        if ($setter === false) {
            return null;
        }

        switch ($this->setterType) {
            case self::ACCESSOR_DEFAULT:
                $this->propertyAccessor->setValue($data, $setter, $value);
                break;

            case self::ACCESSOR_MANUAL:
                $data->{$setter}($value);
                break;

            case self::ACCESSOR_USER_FUNC_ARRAY:
                $args = $setter[1];
                foreach ($args as $name => $tmp) {
                    if ($tmp == '@') {
                        $tmp = $value;
                    }
                    $args[$name] = $tmp;
                }
                call_user_func_array([$data, $setter[0]], $args);
                break;

            case self::ACCESSOR_CALLBACK:
                $setter($data, $value, $this);
                break;
        }

        return $value;
    }

    public function getOption(string $name, mixed $defaultValue = null): mixed
    {
        $value = isset($this->options[$name]) ? $this->options[$name] : null;
        return $value !== null ? $value : $defaultValue;
    }

    public function getLabel(): string
    {
        return $this->getOption('label', $this->getName());
    }

    public function dataCanBeUpdated(mixed $data, mixed $newValue, mixed $oldValue): bool
    {
        $allowUpdate = $this->getOption('allow_update');
        return is_bool($allowUpdate) ? $allowUpdate : $allowUpdate($data, $newValue, $oldValue);
    }

    /**
     * Get the value of name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of column
     */
    public function getColumn(): string
    {
        return $this->getOption('column');
    }

    public function getRow(): ?int
    {
        return $this->getOption('row');
    }



    /**
     * Get the value of value
     */
    public function getValue(?string $transformation = 'reverseTransform'): mixed
    {
        $value = $this->value;


        if ($transformation !== null) {
            if ($transformation === 'reverseTransform') {
                //try to get cached value
                if ($this->reverseTransformedValue !== null) {
                    $value = $this->reverseTransformedValue;
                } else {
                    $transformers = array_reverse($this->transformers);
                    foreach ($transformers as $transformer) {
                        $value = $transformer->{$transformation}($value);
                    }
                    //cache value to multiple usage
                    $this->reverseTransformedValue = $value;
                }
            } else {
                //try to get cached value
                if ($this->transformedValue) {
                    $value = $this->transformedValue;
                } else {
                    foreach ($this->transformers as $transformer) {
                        $value = $transformer->{$transformation}($value);
                    }

                    //cache value to multiple usage
                    $this->transformedValue = $value;
                }
            }
        }

        return $value === null ? $this->getOption('empty_data') : $value;
    }

    /**
     * Set the value of value
     *
     * @return  self
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value of owner
     */
    public function getOwner(): ?HandlerInterface
    {
        return $this->owner;
    }

    /**
     * Set the value of owner
     *
     * @return  self
     */
    public function setOwner(HandlerInterface $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return null;
    }

    public static function getPrefix(): string
    {
        return StringUtil::fqcnToBlockPrefix(static::class) ?: '';
    }

    public function hasChanged(mixed $newValue, mixed $oldValue): bool
    {
        if ($callback = $this->getOption('has_changed_callback')) {
            return $callback($newValue, $oldValue);
        }

        if ($newValue === null && $oldValue !== null || $newValue !== null && $oldValue === null) {
            return true;
        }

        if ($newValue === null && $oldValue === null) {
            return false;
        }

        return $this->hasChangedInner($newValue, $oldValue);
    }

    public function hasChangedInner(mixed $newValue, mixed $oldValue): bool
    {
        return $newValue !== $oldValue;
    }


    public function addTransformer(DataTransformerInterface $transformer, $forceAppend = false): static
    {
        if ($forceAppend) {
            $this->transformers[] = $transformer;
        } else {
            array_unshift($this->transformers, $transformer);
        }

        return $this;
    }

    public function resetModelTransformers(): static
    {
        $this->transformers = [];

        return $this;
    }

    public function resetValues(): static
    {
        $this->value = null;
        $this->transformedValue = null;
        $this->reverseTransformedValue = null;

        return $this;
    }
}
