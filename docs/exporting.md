# Exporting
The CrudBundle allows you to easily export your data from a Table View to CSV files. For doing so follow these steps:

1: Enable the export route in your definition:
```php
    /**
     * {@inheritdoc}
     */
    public static function getCapabilities()
    {
        return [
            Page::INDEX,
            Page::SHOW,
            Page::DELETE,
            Page::EDIT,
            Page::CREATE,
            Page::EXPORT			<----- Export Route
        ];
    }
```

You now see an export button at the bottom of your table.

By default the table configuration will be exported.

## Customization

To define your custom export, just override the `configureExport` method. Create columns as you need them.

```php
    public function configureExport(Table $table)
    {
        $this->configureTable($table);

        $table->addColumn('id', null, [
            Column::OPTION_PRIORITY => 200
        ])
        ->addColumn('jobTitle');
    }
```



## Export Column options

```php
    public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('name', null, [
                Column::OPTION_EXPORT => [
                    Column::OPTION_EXPORT_EXPORTABLE => false
                    Column::OPTION_EXPORT_TEXTWRAP => true
                ]
            ])
        ;
    }
```   

## Multiple Exporter

It is also possible to add your own exporter to the default one and therefore provide multiple exporter to the user. For this you first have to create your own exporter and implement the `ExporterInterface`:

```php
<?php

namespace App\Exporter;

use Symfony\Contracts\Translation\TranslatorInterface;

class ExampleExporter implements ExporterInterface
{


    # Inject the services you need
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function createSpreadsheet(Table $table, $spreadsheet = new Spreadsheet()): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();
        $entries = $table->getRows();
        
        # Build your spreadsheet

        return $spreadsheet;
    }

}
```

In your definition you can add your new exporter to the table. For this override the `configureTableExporter` method and pass the acronym and the exporter:

```php
    public function configureTableExporter(Table $table): void
    {
        parent::configureTableExporter($table);
        # translation key in this example equals to -> wwd.app_entity_example.exporter.acronym_for_translation
        $table->addExporter('acronym_for_translation', $this->exampleExporter); 
    }
```
