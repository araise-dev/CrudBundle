# BlockBlock

This Block is used as a container for other blocks. It is used to create a layout for the page.
## Tabs
You can have Tabs like following:
```php
$base = $builder->addBlock('base', BlockBlock::class, [
    Block::OPT_BLOCK_PREFIX => BlockBlock::OPT_BLOCK_PREFIX_TAB,
]);
$base->addBlock(...);
```

## Grid
You can have a Grid like following:
```php
$base = $builder->addBlock('base', BlockBlock::class, [
    Block::OPT_BLOCK_PREFIX => BlockBlock::OPT_BLOCK_PREFIX_GRID,
    BlockBlock::OPT_LAYOUT_OPTIONS => [
        BlockBlock::OPT_LAYOUT_VERTICALLY => 3,
        BlockBlock::OPT_LAYOUT_HORIZONTALLY => 1,
    ],
]);
$base->addBlock(...);
```

### Combination
You can stack these `BlockBlock` how deep you like. 

## Options
[php-doc-parser(araise-dev/CrudBundle:src/Block/BlockBlock.php:public const OPT_)]

### Extended Options from Block
[php-doc-parser(araise-dev/CrudBundle:src/Block/Block.php:public const OPT_)]
