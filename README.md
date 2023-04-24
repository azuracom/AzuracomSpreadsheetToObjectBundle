Installation
============


### Step 1: Download the Bundle

```js
//composer.json
{
    //...
    "require": {
        //...
        "azuracom/spreadsheet-to-object-bundle": "^1.0"
    },
    "repositories":[
        {
          "type": "vcs",
          "url": "git@github.com:azuracom/AzuracomSpreadsheetToObjectBundle.git"
        }
    ],
}
```

```console
$ composer update
```

If first time downloading a private repo for azuracom, you should have something like this in console:

```console
Could not fetch https://api.github.com/repos/azuracom/AzuracomSpreadsheetToObjectBundle, please review your configured GitHub OAuth token or enter a new one to access private repos
Head to https://github.com/settings/tokens/new?scopes=repo&description=SomeDescription
to retrieve a token. It will be stored in "/home/thibaut/.config/composer/auth.json" for future use by Composer.
Token (hidden): 
```
Open github link, go to the bottom of the page and click on "Generate token", copy new generated token and past to the console

### Step 2: Enable and configure the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Azuracom\SpreadsheetToObject\AzuracomSpreadsheetToObjectBundle::class => ['all' => true],
];
```

Usage
============

## Use as Export


1. Create the builder

```php
<?php

namespace App\Spreadsheet\HandlerBuilder;

use Azuracom\SpreadsheetToObject\ColumnType\BooleanType;
use Azuracom\SpreadsheetToObject\ColumnType\CollectionType;
use Azuracom\SpreadsheetToObject\ColumnType\IntegerType;
use Azuracom\SpreadsheetToObject\ColumnType\MoneyType;
use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Azuracom\SpreadsheetToObject\Factory\HandlerFactoryInterface;
use Symfony\Component\Form\CallbackTransformer;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class OrderExportHandlerBuilder
{
    public function __construct(
        private HandlerFactoryInterface $factory,
    ) {
    }

    public function getHandler()
    {
        $handler = $this->factory->create();

        //the field below are relaive to the order
        $handler
            ->setCurrentKey('order')
            ->add('number', TextType::class, [
                'label' => 'N°Commande', // label used by setSheetHeader NB: this value will be passed to translator
                'column' => 'A' //if not setted, autoincremented from A each time ->add() is called
                'column_width' => 50, //set the column width in pt
            ])
            //use "." to go in sub object
            ->add('customer.firstname', TextType::class, [
                'label' => 'Prénom client',
                'empty_data' => 'Indéfini', //value returned when data is null,
                'column_comment' => "Cette colonne contient le nom du client", //use spreadsheetHandler->setSheetHeaderComments to apply
            ])
            //set column type relatively to the data sent
            ->add('shipped', BooleanType::class, [
                'label' => "Éxpédiée",
                //use callabck to define cell styles
                'cell_styles' => function ($order,$shipped,$columnType) {
                    //should return an array styles
                    return [
                        'font' => [
                            'color' => $shipped ? 
                                ['rgb' => '00a65d'] : //green
                                ['rgb' => 'ed1c24'] //red
                                ,
                        ]
                    ];
                },
                //access cell object directly
                'cell_callback' => function(Cell $cell,$order,$columnType) {
                    $cell->setHyperlink(/* just a sample */);
                }
            ])
            ->add('total', MoneyType::class, [
                'label' => 'Total',
                //apply style on spreadsheet cell: $cell->getStyle()->applyFromArray(...);
                'cell_styles' => [
                    'numberFormat' => [
                        'formatCode' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
                    ]
                ]
            ])
            //the field below are relaive to the order item
            ->setCurrentKey('order_item')
            ->add('product.reference', TextType::class, [
                'label' => 'Référence produit'
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité'
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Prix unitaire'
            ])
            ->add('total', MoneyType::class, [
                'label' => 'Total',
            ])
            ->add('similarProducts', CollectionType::class, [
                'label' => 'Ref produits liés',
                //custom method to retrieve value
                'getter' => function ($orderItem) {
                    if ($association = $orderItem->getProduct()->getAssociationByTypeCode('similar_product')) {
                        return $association->getAssociatedProducts();
                    }
                    return null;
                },
            ]);
        //Add transformer to a specific column
        $handler->get('similarProducts')->addTransformer(new CallbackTransformer(
            //php value to string (used for export)
            function ($v) {
                $output = "";
                foreach ($v as $key =>  $value) {
                    $output .= $value->getReference() . ", ";
                }

                return $output;
            },
            //string to php value (used for import)
            function ($v) {
                return $v;
            }
        ));

        return $handler;
    }
}
```

2. Create the controller

```php
<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Spreadsheet\HandlerBuilder\OrderExportHandlerBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AbstractController
{
    #[Route('/export', name: 'order_export')]
    public function export(OrderExportHandlerBuilder $builder)
    {
        $spreadsheetHandler = $builder->getHandler();
            
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Order export");

        //reuse label to auto set header
        $spreadsheetHandler
            ->setSheetHeader($sheet)
            ->setSheetColumnWidth($sheet)
            ->setSheetHeaderComments($sheet);

        $orders = $this->getDoctrine()->getRepository(Oder::class)->findAll();
        $rowNumber = 2;
        foreach($orders as $order){
            foreach($order->getItems() as $item){
                $spreadsheetHandler
                    //set values to column mapped to "order" key
                    ->setCurrentKey('order')
                    ->setSheetRowContent($sheet,$order,$rowNumber)
                    //set values to column mapped to "order_item" key
                    ->setCurrentKey('order_item')
                    ->setSheetRowContent($sheet,$item,$rowNumber);
                $rowNumber ++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $response = new Response($content);
        $response->headers->add([
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="export.xlsx"'
        ]);

        return $response;
    }
}
```

## Use as export with user choice column

Make sure that stimulus is supported by your application with typescript and install sortablejs

```console
yarn add sortablejs
```

1. Create forms type

```php
<?php
//src/Form/Filter/ExportCustomerColumFilter.php

namespace App\Form\Filter;

use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Azuracom\SpreadsheetToObject\Form\Type\ExportColumnCheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportCustomerColumFilter extends AbstractType
{
        $builder
            ->add('firstname', ExportColumnCheckboxType::class, [
                'label' => 'Prénom', //will override column_options.label and be used to set file header
                //column stuff configuration
                'column_name' => '[firstname]', //not mandatory if equal to form name, NB this notation is used because customer data is an array and not an entity
                'column_type' => TextType::class, //default value
                'column_options' => [
                    //check column type to retrieve available options
                    'empty_data' => 'Indéfini', //value returned when data is null
                ],
                'column_key' => $options['column_key'], //get column key used by the parent
            ])
            ->add('lastname', ExportColumnCheckboxType::class, [                
                'label' => 'Nom',
                'column_name' => '[lastname]',
                'column_key' => $options['column_key'], 
            ]);
    }

    //Don't forget to add this to set column key using a parent ExportColumnGroupType
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('column_key',null);
    }
}

//src/Form/Filter/ExportCustomerAddressColumFilter.php

namespace App\Form\Filter;

use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Azuracom\SpreadsheetToObject\Form\Type\ExportColumnCheckboxType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportCustomerAddressColumFilter extends AbstractType
{
        $builder
            ->add('street', ExportColumnCheckboxType::class, [
                'column_name' => '[street]',
                'label' => 'Rue', 
                'column_key' => $options['column_key'], 
            ])
            ->add('city', ExportColumnCheckboxType::class, [                
                'label' => 'Ville',
                'column_name' => '[city]',
                'column_key' => $options['column_key'], 
            ]);
    }

    //Don't forget to add this to set column key using a parent ExportColumnGroupType
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('column_key',null);
    }
}

```


2. Create controller

```php
<?php

namespace App\Controller;

use App\Form\Filter\ExportCustomerColumFilter;
use Azuracom\SpreadsheetToObject\Factory\HandlerFactoryInterface;
use Azuracom\SpreadsheetToObject\Form\Type\ExportColumnGroupType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractController
{
    #[Route('/export', name: 'order_export')]
    public function export(Request $request, HandlerFactoryInterface $factory)
    {
        $form = $this->createFormBuilder()
            ->add('customer',ExportColumnGroupType::class,[
                'entry_type' => ExportCustomerColumFilter::class,
                'column_key' => 'customer', //Nb: define configureOptions with column_key !!
                'label' => 'Info générale client' //label of check all group checkbox
            ])
            ->add('address',ExportColumnGroupType::class,[
                'entry_type' => ExportCustomerAddressColumFilter::class,
                'column_key' => 'customer_address',
                'label' => 'Info adresses client' 
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            //use form to create handler,only selected column will be added to the handler
            $handler = $factory->createFromForm($form);

            //sample with array data
            $customers =[
                [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'addresses' => [
                        ['street' => 'Avenue de la république' , 'city' => 'Avignon'],
                        ['street' => 'Place pie' , 'city' => 'Avignon'],
                    ]
                ],
                [
                    'firstname' => 'Jane',
                    'lastname' => 'Doe',
                    'addresses' => [],
                ],
            ];
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $handler->setSheetHeader($sheet);
            $rowNumber = 2;
            foreach ($customers as $customer) {
                //no address, add one row with only customer info
                if(count($address) === 0){
                    $handler
                        ->setCurrentKey('customer') //relative to column_key in form
                        ->setSheetRowContent($sheet, $customer, $rowNumber);
                    $rowNumber++;
                }else{
                    //one row by custumer and address
                    foreach($customer['addresses'] as $address){
                        $handler
                            //write customer info
                            ->setCurrentKey('customer') 
                            ->setSheetRowContent($sheet, $customer, $rowNumber)
                            ->setCurrentKey('customer_address') 
                            ->setSheetRowContent($sheet, $address, $rowNumber);
                        $rowNumber++;
                    }
                }                
            }

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            $response = new Response($content);
            $response->headers->add([
                'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="export.xlsx"'
            ]);

            return $response;
        }

        return $this->render('product/export.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
```

3. Create the stimulus controller

```js
// assets/controllers/spreadsheet-column-conf_controller.ts

import { Controller } from '@hotwired/stimulus';
import Sortable from "sortablejs";

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    declare readonly columnSelectorTargets: HTMLFormElement[];
    declare readonly selectedColumnsTarget: HTMLFormElement;
    declare readonly columnIndexesTarget: HTMLFormElement;
    declare sortable: Sortable;

    static targets = ['columnSelector', 'selectedColumns', 'columnIndexes'];

    connect(): void {
        this.sortable = Sortable.create(this.selectedColumnsTarget, {
            // Spécifiez que les éléments `li` sont les éléments pouvant être déplacés
            handle: "li",
            ghostClass: 'sortable-ghost',
            // Définir la fonction `onUpdate` qui se déclenchera lorsque la liste sera modifiée
            onUpdate: () => {
                this.updateColumnIndex()
            }
        });

        //init selected columns
        var items = [];
        this.columnSelectorTargets.forEach(columnSelector => {
            const inputValue = this.getColumnSelectorInput(columnSelector, 'value');
            const inputCheckbox = this.getColumnSelectorInput(columnSelector, 'checkbox');

            if (!inputCheckbox.checked) {
                return;
            }

            items.push({
                columnIndex: inputValue.value,
                input: inputCheckbox,
            })

        });

        items.sort((a: any, b: any): number => {
            return a.columnIndex > b.columnIndex ? 1 : -1;
        });

        items.forEach((item) => {
            this.addSelectedColumn(item.input);
        })
    }

    disconnect(): void {
        this.sortable.destroy();
    }

    columnSelectorTargetConnected = (columnSelector: HTMLElement) => {
        const input = this.getColumnSelectorInput(columnSelector, 'checkbox');
        input.addEventListener('change', this.toggleSelectedColumn);
    }

    columnSelectorTargetDisconnected = (columnSelector: HTMLElement) => {
        const input = this.getColumnSelectorInput(columnSelector, 'checkbox');
        input.removeEventListener('change', this.toggleSelectedColumn);
    }

    toggleGroup = (event: any): void => {
        const container = document.querySelector(event.params.container);
        container.querySelectorAll('input[type=checkbox]').forEach(input => {
            input.checked = event.target.checked;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

    }

    toggleSelectedColumn = (event: any): void => {

        const input = event.target;

        if (input.checked) {
            this.addSelectedColumn(input)
        } else {
            const columnSelector = event.target.closest('[data-spreadsheet-column-conf-target=columnSelector]');
            const selectedColumn = this.selectedColumnsTarget.querySelector(`[data-id='${columnSelector.id}']`);
            if (selectedColumn) {
                selectedColumn.remove();
            }
        }

        this.updateColumnIndex();
    }

    addSelectedColumn = (input: HTMLInputElement) => {
        const columnSelector = input.closest('[data-spreadsheet-column-conf-target=columnSelector]');
        const html = this.selectedColumnsTarget.dataset.elementWidget
            .replace('__label__', document.querySelector(`label[for='${input.id}']`).innerHTML)
            .replace('__id__', columnSelector.id);
        this.selectedColumnsTarget.insertAdjacentHTML('beforeend', html);
    }

    unselectColumn = (event: any): void => {
        const selectedColumn = event.target.closest('li');
        const columnSelector = document.querySelector(`#${selectedColumn.dataset.id}`)
        const input = this.getColumnSelectorInput(columnSelector, 'checkbox');
        input.checked = false;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        selectedColumn.remove();
        this.updateColumnIndex();
    }

    updateColumnIndex = (): void => {
        //loop over all column selector
        this.columnSelectorTargets.forEach(columnSelector => {
            const columnInput = this.getColumnSelectorInput(columnSelector, 'value');
            const selectedColumn = this.selectedColumnsTarget.querySelector(`[data-id='${columnSelector.id}']`);

            //if a selected column exist
            if (selectedColumn) {
                //retrieve the corresponding columnIndex
                const index = Array.from(selectedColumn.parentNode.children).indexOf(selectedColumn);
                const columnIndex: any = this.columnIndexesTarget.children[index];
                const value = columnIndex.innerHTML;
                columnInput.value = value;
            }
            //vlear value
            else {
                columnInput.value = '';
            }
        })

    }

    getColumnSelectorInput = (columnSelector: HTMLElement | Element, type: string): HTMLInputElement => {
        return type === 'checkbox' ?
            columnSelector.querySelector('input[type=checkbox]') :
            columnSelector.querySelector('input[type=hidden]');
    }
}
```

4. Configure fields

```twig
{# templates/form/fields.html.twig #}
{% block export_column_checkbox_row %}
	<div  
        id="{{ form.vars.id }}"
        data-spreadsheet-column-conf-target="columnSelector"
    >
		{{ form_row(form.selected, {row_attr: {class: 'mb-1'}}) }}
        {{ form_widget(form.column) }}
	</div>
{% endblock %}


{% block export_column_group_row %}

	<div id="{{ form.vars.id }}">
		{{ form_row(form.check, {attr:{
            'data-action': 'spreadsheet-column-conf#toggleGroup',
            'data-spreadsheet-column-conf-container-param': '#' ~ form.vars.id ~ '_container'    
        }}) }}

		<ul class="list-unstyled ps-4" id="{{ form.vars.id }}_container">
			{% for field in form.children %}
				<li>{{ form_row(field) }}</li>
			{% endfor %}
		</ul>
	</div>
{% endblock %}
```

don't forget to configure your form theme

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - 'form/fields.html.twig' 
```

4. Create your template



```twig
{% extends 'layout.html.twig' %}


{% macro selectedColumnItem() %}
    <li class='list-group-item p-1 position-relative rounded-0' data-id='__id__' >
        <span class='label-content'>__label__</span>
        <button 
            class='btn btn-default btn-sm position-absolute' 
            style='top:0;right:0' 
            type='button'
            data-action="spreadsheet-column-conf#unselectColumn"
        >
            x
        </button>
    </li>
{% endmacro %}

{% block content %}
    <div class="container">
        <div class="row" {{ stimulus_controller('spreadsheet-column-conf') }}>
            <div class="col-6">            
                {{ form_start(form) }}
                {{ form_row(form) }}                  
                <button type="submit" class="btn btn-success">
                    GO
                </button>
                {{ form_end(form) }}
            </div>
            <div class="col-6">
                <div class="d-flex align-items-stretch flex-row">
                    <ul 
                        class="list-group" 
                        style="flex: 0 0 40px;"
                        data-spreadsheet-column-conf-target='columnIndexes'
                    >
                        {% for column in getColumnFromForm(form) %}
                            <li class="list-group-item rounded-0 p-1">{{ column }}</li>
                        {% endfor %}
                    </ul>
                    <ul 
                        class="list-group" 
                        style="flex: auto;"
                        data-spreadsheet-column-conf-target='selectedColumns'
                        data-element-widget=" {{ _self.selectedColumnItem()|e('html') }}"
                    >
                    </ul>
                </div>
            </div>
        </div>
    </div>
{% endblock %}

```




## Use as Import

1. Create a builder

```php
<?php

namespace App\Spreadsheet\HandlerBuilder;

use App\Entity\Product;
use App\Entity\Seller;
use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use Azuracom\SpreadsheetToObject\ColumnType\ChoiceType;
use Azuracom\SpreadsheetToObject\ColumnType\MoneyType;
use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Azuracom\SpreadsheetToObject\ColumnType\EntityType;
use Azuracom\SpreadsheetToObject\DataTransformer\EntityTransformer;
use Azuracom\SpreadsheetToObject\Factory\HandlerFactoryInterface;
use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Twig\Environment;

class ProductImportHandlerBuilder
{

    public function __construct(
        private HandlerFactoryInterface $factory,
        private EntityManagerInterface $em,
        private Environment $twig
    ) {
    }


    public function getHandler() : HandlerInterface
    {
        $handler = $this->factory->create();

        $handler
            //if you want to retrieve all changes with $handler->getChanges() or use $handler->hasChanged() method
            ->setTrackChanges(true) 
            //by default on each setDataValues() calls changes and errors will be reseted, if you have multiple key in one handler
            //you should pass this to false and manually reset after setting your data values
            ->setAutoReset(false) 
            ->setCurrentKey('product')
            ->add('reference', TextType::class, [
                'allow_update' => false, //this field will only be setted during creation
                'label' => 'Référence',
                'help' => 'La référence du produit'
            ])
            ->add('price', MoneyType::class, [
                //imlpements your own logic to determine if value has changed
                'has_changed_callback' => function ($a, $b) {
                    return $a !== $b; //default logic
                },
                //add constraints to the input data
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => 100]),
                ],
                'label' => 'Prix',
                'help' => 'Montant minimum: 1€'
            ])
            ->add('madeIn', ChoiceType::class, [
                'label' => "Pays de fabrication",
                //override default not found message:
                'not_found_message' => 'Les pays possibles sont France et Belgique!'
                'choices'=> [
                    //valueInFile => valueInObject
                    'France' => 'FR',
                    'FR' => 'FR',
                    'Belgique' => 'BE',
                    'BE' => 'BE'
                ]
            ])
            ->add('description', TextType::class, [
                //errors triggered by validator with this path will be mapped to this column
                'error_match_path' => 'translations[fr].description',
                //custom logic to set value
                'setter' => function (Product $product,$value, ColumnTypeInterface $columnType) {
                    $locale = $columnType->getOption('locale'); //sample for columnType usage, not mandatory
                    $product->getTranslations($locale)->setDescription($value);
                },
                'label' => 'Description',
                'help' => $this->twig->render('path/to/template.html.twig', ['param' => 'paramvalue']),
                'help_is_html' => true,
            ])
            ->add('seller', EntityType::class,[
                'class' => Seller::class,
                'property' => 'code',

                //define method to retrieve object
                'find_method' => 'findBy' //default value is findAll,
                'find_arguments' => [['code'=> $sellerCodes]] //for instance fetch only seller where codes are presents in current file
                //or you can use a query builder
                'query_builder' => function(EntityRepository $er) use ($sellerCodes) {
                    return $er->createQueryBuilder('s')
                        ->where('code IN (:codes)')
                        ->setParameter('codes',$sellerCode)
                },

                //Sample with more advanced usage
                'property' => function($seller) { //function recieve entity as first arguments
                    return $seller->getCode() .'-' . $seller->getCountryCode();
                },
                'find_callback' => function($value,EntityRepository $repository) { //function recieve cell content as first arguments and repository as second
                    //your own logic
                    $explode = explode('-');
                    $code = $explode[0];
                    $countryCode = $explode[1];

                    return $repository->findOneBy([
                        'code' => $code,
                        'countryCode' => $countryCode,
                    ]);
                }


                //create new instance dynamically
                'create_if_not_found' => true,
                'create_callback' => function(Seller $newInstance, $code, $transformer) {
                    $newInstance->setCode($code);
                    $newInstance->setCreatedDynamically(true);
                }
            ])
            ->setCurrentKey('variant')
            ->add('code',TextType::class)
            ->add('name',TextType::class)
            ;

        return $handler;
    }
}
```

2. Create Controller

```php
<?php

namespace App\Controller;

use App\Entity\Product;
use App\Spreadsheet\HandlerBuilder\ProductImportHandlerBuilder;
use Azuracom\SpreadsheetToObject\Error\Error;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/import', name: 'product_import')]
    public function import(Request $request, ProductImportHandlerBuilder $builder)
    {
        $em = $this->getDoctrine()->getManager();
        $spreadsheetHandler = $builder->getHandler(); 

        //create form with file input
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $file = $form->get('file')->getData();

            $objReader = IOFactory::createReaderForFile($file->getPath());
            //load data from target sheet
            $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($url);
            $worksheet = $objPHPExcel->getSheet(0);

            foreach ($worksheet->getRowIterator(2) as $row) {
                //get all values from file and put them in handler memory
                $spreadsheetHandler->setValues($row);

                //sample for retrive an existing object
                $reference = $spreadsheetHandler
                    ->get('reference','product') //if multiple key in the handler, set key has second param
                    ->getValue(); 

                $variantCode = $spreadsheetHandler
                    ->get('code','variant') //if multiple key in the handler, set key has second param
                    ->getValue(); 

                $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy([
                    'reference' => $reference
                ]);
                if(!$product){
                    $product = new Product();
                }

                $variant = '...'; //find variant logic

                $spreadsheetHandler
                    ->setCurrentKey('product')
                    ->setDataValues($product,['Product']) //second arguments is validation group, generally use entity name
                    ->setCurrentKey('variant')
                    ->setDataValues($variant,['ProductVariant']);
                if($spreadsheetHandler->hasError()){
                    /** @var Error */
                    foreach($spreadsheetHandler->getErrors() as $error){
                        $this->addFlash('danger',$error->getMessage());
                    }
                    continue;
                }

                if($product->getId() === null || $spreadsheetHandler->hasChanged()){
                    //flush the changes
                    $em->persist($product);
                    $em->flush();
                }

                //manually reset changes and errors
                $spreadsheetHandler->resetChanges()->resetErrors();
            }
            $this->addFlash('success','File loaded');
        }

        return $this->render('product/import.html.twig',[
            'spreadsheetHandler' => $spreadsheetHandler,
            'form' => $form->createView(),
        ]);
    }
}
```


3. Create a view to help user with file format

```html
{#  templates/product/import.html.twig #}

{% extends 'layout.html.twig' %}

{% block content %}

    <div class="container">
        <div class="row">
            <div class="col-md-6">
                {{ form_start(form) }}
                {{ form_rest(form) }}
                <button class="btn btn-primary">
                    Upload
                </button>
                {{ form_end(form) }}
            </div>
            <div class="col-md-6">
                {% include "@AzuracomSpreadsheetToObject/spreadsheet_description.html.twig" with {handler: spreadsheetHandler} %}
            </div>
        </div>
    </div>
{% endblock %}

```


### Use with sylius attribute

1. Create column Type

```php
<?php

namespace App\Spreadsheet\ColumnType;

use Azuracom\SpreadsheetToObject\ColumnType\AttributeType;
use Sylius\Component\Resource\Factory\FactoryInterface;

class ProductAttributeType extends AttributeType
{
    public function __construct(FactoryInterface $productAttributeValueFactory,$defaultLocale)
    {
        $this->factory = $productAttributeValueFactory;
        $this->locale = $defaultLocale;
    }
}
```

if the factory doesn't autowire:


```yaml
#config/services.yaml
services:
    #bind factory if multiple usage
    _defaults:
        bind:
            $defaultLocale: '%locale%'
            $productAttributeValueFactory: '@sylius.factory.product_attribute_value'
    #or set factoru for the service
    App\Spreadsheet\ColumnType\PlotAttributeType:
        arguments:
            $productAttributeValueFactory: '@sylius.factory.product_attribute_value'
            $defaultLocale: '%locale%'
```

2. Build column

```php
<?php

namespace App\Spreadsheet\HandlerBuilder;

use App\Entity\Product;
use App\Entity\ProductAttribute;
use App\Spreadsheet\ColumnType\ProductAttributeType;
use Azuracom\SpreadsheetToObject\Factory\HandlerFactoryInterface;
use Azuracom\SpreadsheetToObject\Spreadsheet\HandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\AttributeType\CheckboxAttributeType;
use Azuracom\SpreadsheetToObject\DataTransformer\BooleanTransformer;

class ProductImportHandlerBuilder
{

    public function __construct(
        private HandlerFactoryInterface $factory,
        private EntityManagerInterface $em,
    ) {
    }

    public function getHandler() : HandlerInterface
    {
        $handler = $this->factory->create();

        $attributes = $this->em->getRepository(ProductAttribute::class)->findBy([], ['position' => 'ASC']);
        foreach ($attributes as $attribute) {
            $handler->add($attribute->getCode(), ProductAttributeType::class, [
                'attribute' => $attribute,
                //manualy set type inner transformer
                'inner_transformer' => [
                    CheckboxAttributeType::TYPE => new BooleanTransformer(['oui'],['non']),
                ]
            ]);
        }

        return $handler;
    }
}

```

### Use with AzuracomProcessBundle

```php
<?php

namespace App\Process;

use App\Entity\Product;
use App\Spreadsheet\HandlerBuilder\ProductHandlerBuilder;
//check this class for more details on available tools
use Azuracom\ProcessBundle\Handler\AbstractSpreadsheetHandler; 
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

class ImportProductHandler extends AbstractSpreadsheetHandler implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    const TYPE_DEFAULT = self::class;

    public function configure(): void
    {
        //in this method you should call parent, configure the spreadsheetHandler
        parent::configure();

        $this->spreadsheetHandler = $this->spreadSheetHandlerBuilder()
            ->getHandler();

        /** @var Product[] */
        $products = $this->em->getRepository(Product::class)->findAll();
        foreach ($products as $product) {
            $this->addProductInDataMatcher($product);
        }
    }


    private function addProductInDataMatcher(Product $product)
    {
        $this->dataMatcher
            ->addData('product', $product)
            ->addMatch($product->getReference());
    }

    #[SubscribedService]
    private function spreadSheetHandlerBuilder(): ProductHandlerBuilder
    {
        return $this->container->get(__METHOD__);
    }

    protected function read(Worksheet $worksheet): void
    {
        foreach ($worksheet->getRowIterator(2) as $row) {
            $this->spreadsheetHandler->setValues($row);
            $reference = $this->spreadsheetHandler->get('reference')->getValue();

            //check if product exists or create
            if (!$product = $this->dataMatcher->findData('product', $reference)) {
                $product = new Product();
                $product->setReference($reference);
                $this->counter->increment('created');
                //add in datamatcher to handle multiple occurence in single file
                $this->addProductInDataMatcher($product);
                $this->em->persist($product);
            }

            $this->spreadsheetHandler->setDataValues($product);
            foreach ($this->spreadsheetHandler->getErrors() as $error) {
                $this->helper->error($error->getMessage());
            }

            //be sure that trackChanges is enabled in the spreadSheet handler if you want to increment only when updated
            if ($this->spreadsheetHandler->hasChanged() && !$this->spreadsheetHandler->hasError() && $product->getId() !== null) {
                $this->counter->increment('updated');
            }
        }
    }

    protected function getClearClassNames(): array
    {
        //class to clear when file has error, generally all entity class edited with data from spreadsheet file
        return [
            Product::class,
        ];
    }

    protected function getSuccessMessage(): string
    {
        return sprintf(
            "%s produit(s) mis à jour, %s produit(s) créé(s)",
            $this->counter->get('updated'),
            $this->counter->get('created')
        );
    }

    public static function getTypeLabel($type): string
    {
        return "Import Produit";
    }
}

```