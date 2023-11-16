# Table Configuration

For a basic table configuration refer to the [araiseTableBundle Documentations](https://araise-dev.github.io/TableBundle/#/)

```php
class LocationDefinition extends AbstractDefinition
{
    // ...

    public function configureTable(Table $table)
    {
        $table
            ->addColumn(
                'name', 
                null, [
                    'label' => 'Name',
                ]
            )
            ->addColumn('zip', null, ['label' => 'ZIP']);
    }

    // ...
}
```

## Filter with joins

It is possible to create filters based on columns that have to be joined.  
FilterTypes accept an array of filters.

### Automatically added Filters

By default, the bundle tries to generate filters for you. But this doesn't work in all cases. 
That's why you should always check the filters and fix or remove the broken ones.

#### Remove an automatically generated filter
You can let the CrudBundle handle the generation of filters and remove some of them like this:
```php
public function configureFilters(Table $table): void
{
    parent::configureFilters($table);

    $table->getFilterExtension()?->removeFilter('id');
}
```

### Custom Filters
#### Simple
Filter all rooms included in a house with a specific color.  
This filter would be applied on the `RoomDefinition`.

```php
public function configureFilters(Table $table): void
{
    parent::configureFilters($table);
    $filterExtension = $table->getFilterExtension();

    $filterExtension->addFilter('roofColor', 'Roof Color',
        new AjaxRelationFilterType('houseRoof.color', HouseColor::class, $this->doctrine,
            [
                'house' => self::getQueryAlias().'house',
                'houseRoof' => 'house.roof'
            ]
        )
    );
}
```

### Advanced

In this example we join a ManyToOne (Room -> House) and then a OneToMany (House -> Furniture) relation.  
The goal is to filter all rooms contained in all houses which include furniture with a specific status (StatusEnum).  
This filter would be applied on the `RoomDefinition` as well.

```php
public function configureFilters(Table $table): void
{
    parent::configureFilters($table);
    $filterExtension = $table->getFilterExtension();

    $filterExtension->addFilter('houseIncludesFurniture', 'House includes furniture', new SimpleEnumFilterType('houseFurniture.status', [
        'house' => ['innerJoin', self::getQueryAlias().'.house'],
        'houseFurniture' => ['innerJoin', Furniture::class, 'WITH', 'houseFurniture.house = house.id'],
    ], FurnitureStatus::class));
}
```

### Action Buttons
The CrudBundle will add two Action Buttons to each row (show / edit). To add more use the same method `configureTableActions`.

```php
public function configureTableActions(Table $table): void
{
    parent::configureTableActions($table);
    
    $table->addAction('acronym', [
        Action::OPT_ICON => 'icon',
        Action::OPT_LABEL => 'label',
        Action::OPT_ROUTE => 'route',
    ]);
}
```

Where:
- `icon` = fa icon suffix (for "fa-clone" just write "clone")
- `label` = the text on the button
- `route` = route name (CrudBundle will pass the entity "id" to the route generator)

#### Actions that need an entity
If you need access on the data of the entity to build your route (for example because you need the ID in the route parameters), you can use the `configureActions` method.

You can find an example [here](cookbook/custom_actions?id=custom-actions).
