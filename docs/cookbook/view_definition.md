# View Definition

## Prefill a relation field in forms
You can define which data row the definition uses as the default value of an input field.
This way you can either give the user advice on what you think might be right in this situation or, if you hide the input field, you can automatically fill in the correct relation for the user without him having to do anything.

As an example we use an imaginary Book/Author management software: We would like to add a button to an authors page that allows us to create a new book with the author already prefilled.
In order to achieve this, we need to add the `Content::OPT_PRESELECT_DEFINITION` and the `class` form option to the content of the `BookDefinition` like so:

```php
// BookDefinition
public function configureView(DefinitionBuilder $builder, $data): void
{
    parent::configureView($builder, $data);

    $builder
        ->addBlock('base')
        ->addContent('author', null, [
            Content::OPT_DEFAULT_VALUE => $entity->getAuthor(),
            Content::OPT_PRESELECT_DEFINITION => AuthorDefinition::class,
            Content::OPT_FORM_OPTIONS => [
                'class' => Author::class,
            ],
        ])
    ;
}
```

### Linking to the prefilled form
On the other side, in the AuthorDefinition, we can now create the link to this page like so:

```php
// AuthorDefinition
public function configureActions(mixed $data): void
{
    parent::configureActions($data);

    if ($data && $this->getPage() === Page::SHOW)
    {
        $this->addAction('add_book', [
            'route' => BookDefinition::getRoute(Page::CREATE),
            'route_parameters' => [
                self::getQueryAlias() => $data->getId(),
            ],
        ]);
    }
}
```

This adds a button on the detail page of an author that links to the create page of a book with the author preselected.

### Hiding the input field
If you want to hide the input field in the form, you can do so by using the `EntityHiddenType` as the form type like so:

```php
// BookDefinition
public function configureView(DefinitionBuilder $builder, $data): void
{
    parent::configureView($builder, $data);

    $builder
        ->addBlock('base')
        ->addContent('author', null, [
            Content::OPT_DEFAULT_VALUE => $entity->getAuthor(),
            Content::OPT_PRESELECT_DEFINITION => AuthorDefinition::class,
            Content::OPT_FORM_OPTIONS => [
                'class' => Author::class,
            ],
            Content::OPT_FORM_TYPE => EntityHiddenType::class,
        ])
    ;
}
```

This way the user will not see the input field but the value will still be prefilled.
