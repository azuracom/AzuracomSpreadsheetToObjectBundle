<?php

namespace Azuracom\SpreadsheetToObjectBundle\CellType;

use Azuracom\SpreadsheetToObjectBundle\DataTransformer\ExcelDateTimeTransformer;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExcelDateTimeCell extends AbstractCell
{

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'format' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'cell_styles' => function (Options $options) {
                return [
                    'numberFormat' => [
                        'formatCode' => $options['format']
                    ]
                ];;
            }
        ]);
    }

    public function getDefaultTransformer($options): ?DataTransformerInterface
    {
        return new ExcelDateTimeTransformer();
    }

    public function hasChangedInner($newValue, $oldValue): bool
    {
        if ($newValue instanceof \DateTime && $oldValue instanceof \DateTime) {
            return $newValue->getTimestamp() !== $oldValue->getTimestamp();
        }
        return true;
    }
}
