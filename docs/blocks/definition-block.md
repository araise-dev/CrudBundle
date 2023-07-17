# DefinitionBlock
This Block is used to link directly to another definition. Imaging you are creating a booking system. With this Block
you will be able to edit the person who is booking the appointment directly in you booking without defining code twice. 

## Example
In the `BookingDefinition` we add a block referencing to the `PersonDefinition`. Of this definition we add the `base` block. 
If you wish you can edit this block in the provided callback function. 
```php
$builder
    ->addBlock('person', DefinitionBlock::class, [
        DefinitionBlock::OPT_CONFIGURE => function (Block $block) {
            // do some special configuration just for this subpage
        },
        DefinitionBlock::OPT_DEFINITION => PersonDefinition::class,
        DefinitionBlock::OPT_BLOCK => 'base',
        DefinitionBlock::OPT_ACCESSOR_PATH => 'patient',
        Block::OPT_VISIBILITY => [Page::SHOW, Page::EDIT],
    ])
;
```

## Options
[php-doc-parser(araise-dev/CrudBundle:src/Block/DefinitionBlock.php:public const OPT_)]

### Extended Options from Block
[php-doc-parser(araise-dev/CrudBundle:src/Block/Block.php:public const OPT_)]
