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

namespace App\SpreadsheetHandlerBuilder;

use Azuracom\SpreadsheetToObject\ColumnType\BooleanType;
use Azuracom\SpreadsheetToObject\ColumnType\CollectionType;
use Azuracom\SpreadsheetToObject\ColumnType\IntegerType;
use Azuracom\SpreadsheetToObject\ColumnType\MoneyType;
use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Azuracom\SpreadsheetToObject\Factory\HandlerFactoryInterface;
use Symfony\Component\Form\CallbackTransformer;

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
            ])
            //use "." to go in sub object
            ->add('customer.firstname', TextType::class, [
                'label' => 'Prénom client',
                'empty_data' => 'Indéfini' //value returned when data is null
            ])
            //set column type relatively to the data sent
            ->add('shipped', BooleanType::class, [
                'label' => "Éxpédiée",
            ])
            ->add('total', MoneyType::class, [
                'label' => 'Total'
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
use App\SpreadsheetHandlerBuilder\OrderExportHandlerBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractController
{
    #[Route('/export', name: 'order_export')]
    public function export(OrderExportHandlerBuilder $builder)
    {
        $spreadsheetHandler = $builder->getHandler();
            
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Order export");

        //reuse label to auto set header
        $spreadsheetHandler->setSheetHeader($sheet);

        $orders = $this->getDoctrine()->getRepository(Oder::class)->findAll();
        $rowNumber = 2;
        foreach($orders as $order){
            //set values to column mapped to "order" key
            $spreadsheetHandler
                ->setCurrentKey('order')
                ->setSheetRowContent($sheet,$order,$rowNumber);

            foreach($order->getItems() as $item){
                //set values to column mapped to "order" key
                $spreadsheetHandler
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

## Use as Import

1. Create a builder

```php
<?php

namespace App\SpreadsheetHandlerBuilder;

use App\Entity\Product;
use App\Entity\Seller;
use Azuracom\SpreadsheetToObject\ColumnType\ColumnTypeInterface;
use Azuracom\SpreadsheetToObject\ColumnType\MoneyType;
use Azuracom\SpreadsheetToObject\ColumnType\TextType;
use Azuracom\SpreadsheetToObject\DataTransformer\EntityTransformer;
use Azuracom\SpreadsheetToObject\Factory\HandlerFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
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


    public function getHandler()
    {
        $handler = $this->factory->create();

        $handler
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
            ->add('description', TextType::class, [
                //errors triggered by validator with this path will be mapped to this column
                'error_match_path' => 'translations[fr].description',
                //custom logic to set value
                'setter' => function (Product $product, ColumnTypeInterface $columnType) {
                    $value = $columnType->getValue();
                    $product->getTranslations('fr')->setDescription($value);
                },
                'label' => 'Description',
                'help' => $this->twig->render('path/to/template.html.twig', ['param' => 'paramvalue']),
                'help_is_html' => true,
            ])
            ->add('seller', TextType::class);


        $handler->get('seller')->addTransformer(new EntityTransformer(
            $this->em->getRepository(Seller::class), //repo
            'code', //property used to match value in cell
            'findEnabled' // method used to retrieve all elements
        ));

        return $handler;
    }
}
```

2. Create Controller

```php
<?php

namespace App\Controller;

use App\Entity\Product;
use App\SpreadsheetHandlerBuilder\ProductImportHandlerBuilder;
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
                $reference = $spreadsheetHandler->get('reference')->getValue();
                $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy([
                    'reference' => $reference
                ]);
                if(!$product){
                    $product = new Product();
                }

                $spreadsheetHandler->setDataValues($product,['validation_groups_name']);
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